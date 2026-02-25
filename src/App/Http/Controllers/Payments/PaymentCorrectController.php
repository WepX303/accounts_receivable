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

class PaymentCorrectController extends Controller
{
    public function __invoke(Request $request, CreditPayment $payment)
    {
        // ✅ Admin-only
        $user = Auth::user();
        if (!$user || $user->role !== UserRoleEnum::ADMIN) {
            return back()->with('warning', 'Only Admin can correct payments.');
        }

        // ✅ Cannot correct already voided
        if ($payment->voided_at) {
            return back()->with('warning', 'This payment is already voided.');
        }

        $data = $request->validate([
            'payment_at' => ['nullable', 'date'],
            'payment_method' => ['required', 'in:cash,card,mixed,phone'],
            'pay_amount' => ['required', 'numeric', 'min:0.01'],
            'cash_total' => ['nullable', 'numeric', 'min:0'],
            'card_total' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'reason' => ['nullable', 'string', 'max:300'],
        ]);

        $enteredAt = now();
        $now = !empty($data['payment_at']) ? Carbon::parse($data['payment_at']) : $enteredAt;

        if ($now->isFuture()) {
            return back()->with('warning', 'Future payment date is not allowed.')->withInput();
        }

        $method = $data['payment_method'];
        $received = $this->toMoney($data['pay_amount']);
        $cashTotal = $this->toMoney($data['cash_total'] ?? 0);
        $cardTotal = $this->toMoney($data['card_total'] ?? 0);

        // normalize totals by method
        if ($method === 'cash') {
            $cashTotal = $received;
            $cardTotal = 0.0;
        } elseif ($method === 'card' || $method === 'phone') {
            $cashTotal = 0.0;
            $cardTotal = $received;
        } else {
            if (abs(($cashTotal + $cardTotal) - $received) > 0.01) {
                return back()->with('warning', 'Mixed: Cash + Card must equal Pay Amount.')->withInput();
            }
        }

        $note = trim((string)($data['note'] ?? ''));
        $note = preg_replace('/\s+/', ' ', $note);

        $reason = trim((string)($data['reason'] ?? ''));
        $reason = preg_replace('/\s+/', ' ', $reason);

        try {
            DB::transaction(function () use ($payment, $user, $enteredAt, $now, $method, $received, $cashTotal, $cardTotal, $note, $reason) {

                // lock payment row
                $p = CreditPayment::query()->lockForUpdate()->findOrFail($payment->id);

                if ($p->voided_at) {
                    throw new \RuntimeException('This payment is already voided.');
                }

                // lock credit
                $c = Credit::query()
                    ->where('logicalref', (int)$p->credit_logicalref)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($c->amount_local === null || $c->paid_local === null) {
                    throw new \RuntimeException('Local debt data missing. Cannot correct.');
                }

                $totalLocal = (float)$c->amount_local;
                $paidLocal = (float)$c->paid_local;

                // ---------- STEP 1: VOID OLD PAYMENT ----------
                // old apply = pay - change
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
                    'void_reason' => $reason !== '' ? $reason : 'Corrected',
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

                CreditPayment::create([
                    'credit_logicalref' => (int)$c->logicalref,

                    'customer_name' => mb_substr((string)$c->name, 0, 255),
                    'customer_phone' => mb_substr((string)$c->phone, 0, 50),
                    'customer_passport' => mb_substr((string)$c->passport, 0, 50),
                    'customer_contract' => mb_substr((string)$c->contract, 0, 50),
                    'branch' => mb_substr((string)$c->branch, 0, 50),

                    'created_by' => (int)$user->id,
                    'created_by_name' => mb_substr((string)$user->full_name, 0, 255),
                    'created_by_email' => mb_substr((string)$user->email, 0, 255),
                    'created_by_phone' => mb_substr((string)$user->phonenumber, 0, 50),

                    'pay_amount' => $this->fmtMoney($received),
                    'change_amount' => $this->fmtMoney($change),

                    'method' => $method,
                    'cash_amount' => $this->fmtMoney($cashTotal),
                    'card_amount' => $this->fmtMoney($cardTotal),

                    'old_amount_local' => $this->fmtMoney($remaining),
                    'new_amount_local' => $this->fmtMoney($newRemaining),

                    'old_paid_local' => $this->fmtMoney($paidAfterVoid),
                    'new_paid_local' => $this->fmtMoney($newPaidLocal),

                    'note' => $note !== '' ? $note : null,
                    'created_at' => $now,

                    // optional: link
                    'corrected_from_payment_id' => $p->id, // eğer kolon eklediysen
                ]);
            });
        } catch (\Throwable $e) {
            $msg = $e instanceof \RuntimeException ? $e->getMessage() : 'Correction failed.';
            return back()->with('warning', $msg)->withInput();
        }

        return back()->with('success', 'Payment corrected successfully.');
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