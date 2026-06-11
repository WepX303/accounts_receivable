<?php

namespace App\Http\Controllers\Payments;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\AuditLogger;

class PaymentCorrectController extends Controller
{
    public function __invoke(Request $request, CreditPayment $payment)
    {

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user || !$user->canVoidPayments()) {
            return back()->with('warning', __('validations/validations.payment_void.admin_only'));
        }

        $audit = app(AuditLogger::class);
        $auditData = [];

        // Cannot correct already voided
        if ($payment->voided_at) {
            return back()->with('warning', __('validations/validations.payment_correct.already_voided'));
        }

        $data = $request->validate([
            'payment_at' => ['nullable', 'date'],
            'payment_method' => ['required', 'in:cash,card,mixed,phone'],
            'pay_amount' => ['required', 'numeric', 'min:0.01'],
            'cash_total' => ['nullable', 'numeric', 'min:0'],
            'card_total' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'reason' => ['nullable', 'string', 'max:300'],
        ], [
            'payment_at.date' => __('validations/validations.payment_correct.payment_at_date'),

            'payment_method.required' => __('validations/validations.payment_correct.payment_method_required'),
            'payment_method.in' => __('validations/validations.payment_correct.payment_method_invalid'),

            'pay_amount.required' => __('validations/validations.payment_correct.pay_amount_required'),
            'pay_amount.numeric' => __('validations/validations.payment_correct.pay_amount_numeric'),
            'pay_amount.min' => __('validations/validations.payment_correct.pay_amount_min'),

            'cash_total.numeric' => __('validations/validations.payment_correct.cash_total_numeric'),
            'cash_total.min' => __('validations/validations.payment_correct.cash_total_min'),

            'card_total.numeric' => __('validations/validations.payment_correct.card_total_numeric'),
            'card_total.min' => __('validations/validations.payment_correct.card_total_min'),

            'note.string' => __('validations/validations.payment_correct.note_string'),
            'note.max' => __('validations/validations.payment_correct.note_max'),

            'reason.string' => __('validations/validations.payment_correct.reason_string'),
            'reason.max' => __('validations/validations.payment_correct.reason_max'),
        ]);
        $enteredAt = now();
        $now = !empty($data['payment_at']) ? Carbon::parse($data['payment_at']) : $enteredAt;

        if ($now->isFuture()) {
            return back()->with('warning', __('validations/validations.payment_correct.future_payment_date_not_allowed'))->withInput();
        }

        $method = $data['payment_method'];
        $received = $this->toMoney($data['pay_amount']);
        $cashTotal = $this->toMoney($data['cash_total'] ?? 0);
        $cardTotal = $this->toMoney($data['card_total'] ?? 0);
        $phoneTotal = 0.0;

        // Normalize totals by method
        if ($method === 'cash') {
            $cashTotal = $received;
            $cardTotal = 0.0;
            $phoneTotal = 0.0;
        } elseif ($method === 'card') {
            $cashTotal = 0.0;
            $cardTotal = $received;
            $phoneTotal = 0.0;
        } elseif ($method === 'phone') {
            $cashTotal = 0.0;
            $cardTotal = 0.0;
            $phoneTotal = $received;
        } else {
            $phoneTotal = 0.0;

            if (abs(($cashTotal + $cardTotal) - $received) > 0.01) {
                return back()->with('warning', __('validations/validations.payment_correct.mixed_sum_must_equal'))->withInput();
            }
        }

        $note = trim((string)($data['note'] ?? ''));
        $note = preg_replace('/\s+/', ' ', $note);

        $reason = trim((string)($data['reason'] ?? ''));
        $reason = preg_replace('/\s+/', ' ', $reason);

        try {
            DB::transaction(function () use ($payment, $user, $enteredAt, $now, $method, $received, $cashTotal, $cardTotal, $phoneTotal, $note, $reason, &$auditData) {

                // lock payment row
                $p = CreditPayment::query()->lockForUpdate()->findOrFail($payment->id);

                if ($p->voided_at) {
                    throw new \RuntimeException(__('validations/validations.payment_correct.already_voided'));
                }

                // lock credit
                // $c = Credit::query()
                //     ->where('logicalref', (int)$p->credit_logicalref)
                //     ->lockForUpdate()
                //     ->firstOrFail();

                $c = Credit::query()
                    ->where('active', true)
                    ->where('logicalref', (int) $p->credit_logicalref)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($c->amount_local === null || $c->paid_local === null) {
                    throw new \RuntimeException(__('validations/validations.payment_correct.local_debt_missing'));
                }

                $totalLocal = (float)$c->amount_local;
                $paidLocal = (float)$c->paid_local;

                // ---------- STEP 1: VOID OLD PAYMENT ----------
                // Old apply = pay - change
                $oldReceived = (float)$p->pay_amount;
                $oldChange   = (float)($p->change_amount ?? 0);
                $oldApplied  = $oldReceived - $oldChange;
                if ($oldApplied < 0) $oldApplied = 0;

                // revert paid_local by old applied
                $paidAfterVoid = $paidLocal - $oldApplied;
                if ($paidAfterVoid < 0) $paidAfterVoid = 0;

                $p->forceFill([
                    'voided_at' => $enteredAt,
                    'voided_by' => (int)$user->id,
                    'void_reason' => $reason !== '' ? $reason : __('validations/validations.payment_correct.corrected_default_reason'),
                ])->save();

                $c->forceFill([
                    'paid_local' => $this->fmtMoney($paidAfterVoid),
                    'paid_updated_by' => (int)$user->id,
                    'paid_updated_at' => $enteredAt,
                    'paid_note' => 'correct:void_old | old_payment_id=' . $p->id . ' | old_applied=' . $this->fmtMoney($oldApplied),
                ])->save();

                // ---------- STEP 2: CREATE NEW PAYMENT ----------
                $remaining = $totalLocal - $paidAfterVoid;
                if ($remaining < 0) $remaining = 0;

                // Same rules as store()
                $remaining = round($remaining, 2);
                $received  = round($received, 2);

                if ($remaining <= 0.01) {
                    throw new \RuntimeException(__('validations/validations.payment_correct.debt_closed_use_void_only'));
                }

                $maxExtra = 100; // same as store
                if ($received > $remaining + $maxExtra) {
                    throw new \RuntimeException(
                        __('validations/validations.payment_correct.overpayment_too_high', [
                            'max' => number_format($maxExtra, 2),
                        ])
                    );
                }

                $apply = min($received, $remaining);
                $change = $received - $apply;

                $newPaidLocal = $paidAfterVoid + $apply;
                $newRemaining = $totalLocal - $newPaidLocal;
                if ($newRemaining < 0) $newRemaining = 0;

                $detail = [
                    'corrected=yes',
                    'corrected_from_payment_id=' . $p->id,
                    'payment_at=' . $now->format('Y-m-d H:i:s'),
                    'entered_at=' . $enteredAt->format('Y-m-d H:i:s'),
                    'method=' . $method,
                    'received=' . $this->fmtMoney($received),
                    'applied=' . $this->fmtMoney($apply),
                    'change=' . $this->fmtMoney($change),
                    'cash=' . $this->fmtMoney($cashTotal),
                    'card=' . $this->fmtMoney($cardTotal),
                    'phone=' . $this->fmtMoney($phoneTotal),
                    'total_local=' . $this->fmtMoney($totalLocal),
                    'old_paid_local=' . $this->fmtMoney($paidAfterVoid),
                    'new_paid_local=' . $this->fmtMoney($newPaidLocal),
                    'old_remaining=' . $this->fmtMoney($remaining),
                    'new_remaining=' . $this->fmtMoney($newRemaining),
                ];

                $finalNote = implode(' | ', $detail);
                if ($note !== '') {
                    $finalNote .= ' | note=' . $note;
                }

                $c->forceFill([
                    'paid_local' => $this->fmtMoney($newPaidLocal),
                    'paid_updated_by' => (int)$user->id,
                    'paid_updated_at' => $now,
                    'paid_note' => $finalNote,
                ])->save();

                // CreditPayment::create([
                $newPayment = CreditPayment::create([

                    'credit_logicalref' => (int)$c->logicalref,

                    'customer_name' => mb_substr((string)$c->name, 0, 255),
                    'customer_phone' => mb_substr((string)$c->phone, 0, 50),
                    'customer_passport' => mb_substr((string)$c->passport, 0, 50),
                    'customer_contract' => mb_substr((string)$c->contract, 0, 50),
                    'branch' => mb_substr((string)$c->branch, 0, 50),

                    // PAYMENT RECEIVED BY: ORIGINAL CASHIER TO REMAIN
                    'created_by' => $p->created_by,
                    'created_by_name' => mb_substr((string) ($p->created_by_name ?? 'N/A'), 0, 255),
                    'created_by_email' => mb_substr((string) ($p->created_by_email ?? ''), 0, 255),
                    'created_by_phone' => mb_substr((string) ($p->created_by_phone ?? ''), 0, 50),

                    'pay_amount' => $this->fmtMoney($received),
                    'change_amount' => $this->fmtMoney($change),

                    'method' => $method,
                    'cash_amount' => $this->fmtMoney($cashTotal),
                    'card_amount' => $this->fmtMoney($cardTotal),
                    'phone_amount' => $this->fmtMoney($phoneTotal),

                    'old_amount_local' => $this->fmtMoney($remaining),
                    'new_amount_local' => $this->fmtMoney($newRemaining),

                    'old_paid_local' => $this->fmtMoney($paidAfterVoid),
                    'new_paid_local' => $this->fmtMoney($newPaidLocal),

                    'note' => $note !== '' ? $note : null,
                    'created_at' => $now,

                    // EDITED BY: ADMIN/OPERATOR
                    'corrected_by' => (int) $user->id,
                    'corrected_at' => $enteredAt,
                    'correct_reason' => $reason !== '' ? $reason : null,

                    'corrected_from_payment_id' => $p->id,
                ]);

                $auditData = [
                    'payment' => $newPayment,
                    'old_values' => [
                        'old_payment_id' => $p->id,
                        'old_pay_amount' => $oldReceived,
                        'old_change_amount' => $oldChange,
                        'old_applied' => $oldApplied,
                        'paid_local_before' => $paidLocal,
                    ],
                    'new_values' => [
                        'new_payment_id' => $newPayment->id,
                        'new_pay_amount' => $received,
                        'new_change_amount' => $change,
                        'new_applied' => $apply,
                        'paid_local_after' => $newPaidLocal,
                    ],
                    'extra' => [
                        'credit_logicalref' => (int) $c->logicalref,
                        'method' => $method,
                        'cash_amount' => $cashTotal,
                        'card_amount' => $cardTotal,
                        'phone_amount' => $phoneTotal,
                        'reason' => $reason,
                        'customer_name' => $c->name,
                        'customer_contract' => $c->contract,
                        'corrected_from_payment_id' => $p->id,
                    ],
                ];
            });
        } catch (\Throwable $e) {
            $msg = $e instanceof \RuntimeException
                ? $e->getMessage()
                : __('validations/validations.payment_correct.correction_failed');

            $audit->log(
                action: 'payment_correct_failed',
                category: 'payment',
                subject: $payment ?? null,
                oldValues: null,
                newValues: null,
                extra: [
                    'payment_id' => $payment->id ?? null,
                    'payment_method' => $method ?? null,
                    'pay_amount' => $received ?? null,
                    'reason' => $reason ?? null,
                    'error' => $msg,
                ],
                message: 'Payment correction failed',
                isSuccess: false,
                severity: 'critical',
                isSuspicious: true
            );

            return back()->with('warning', $msg)->withInput();
        }


        if (!empty($auditData)) {
            $audit->log(
                action: 'payment_corrected',
                category: 'payment',
                subject: $auditData['payment'],
                oldValues: $auditData['old_values'],
                newValues: $auditData['new_values'],
                extra: $auditData['extra'],
                message: 'Payment corrected',
                isSuccess: true,
                severity: 'critical',
                isSuspicious: true
            );

            $audit->alert(
                alertType: 'payment_corrected',
                riskLevel: 'critical',
                message: 'A payment was corrected',
                meta: $auditData['extra']
            );
        }

        return back()->with('success', __('validations/validations.payment_correct.corrected_success'));
    }

    private function toMoney($v): float
    {
        $s = trim((string)$v);
        if ($s === '') return 0.0;

        $s = str_replace([' ', "\u{00A0}"], '', $s);

        $hasDot = str_contains($s, '.');
        $hasComma = str_contains($s, ',');

        if ($hasDot && $hasComma) {
            $lastDot = strrpos($s, '.');
            $lastComma = strrpos($s, ',');
            if ($lastComma > $lastDot) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } else {
            if ($hasComma && !$hasDot) $s = str_replace(',', '.', $s);
        }

        $n = (float)$s;
        if (!is_finite($n)) return 0.0;
        return round($n, 2);
    }

    private function fmtMoney(float $n): string
    {
        return number_format(round($n, 2), 2, '.', '');
    }
}
