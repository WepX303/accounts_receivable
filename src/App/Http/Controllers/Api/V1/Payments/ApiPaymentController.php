<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditPaymentResource;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Services\AuditLogger;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiPaymentController extends Controller
{
    public function history(Request $request, Credit $credit)
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $payments = CreditPayment::query()
            ->with([
                'correctedByUser:id,firstname,lastname',
                'voidedByUser:id,firstname,lastname',
            ])
            ->where('credit_logicalref', $credit->logicalref)
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 20))
            ->appends($request->query());

        return ApiResponse::paginated(
            $payments,
            CreditPaymentResource::collection($payments->getCollection())->resolve(),
            'Payment history fetched successfully.'
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => ['required', 'integer'],
            'payment_method' => ['required', 'in:cash,card,mixed,phone'],
            'pay_amount' => ['required', 'numeric', 'min:0.01'],
            'cash_total' => ['nullable', 'numeric', 'min:0'],
            'card_total' => ['nullable', 'numeric', 'min:0'],
            'payment_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $audit = app(AuditLogger::class);

        $customerId = (int) $request->input('customer_id');
        $method = (string) $request->input('payment_method');
        $received = round((float) $request->input('pay_amount'), 2);
        $cashTotal = round((float) $request->input('cash_total', 0), 2);
        $cardTotal = round((float) $request->input('card_total', 0), 2);
        $phoneTotal = 0.0;

        $note = trim((string) $request->input('note', ''));
        $note = preg_replace('/\s+/', ' ', $note);
        $note = $note !== '' ? $note : null;

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
                return ApiResponse::error(
                    'Mixed total must equal received amount.',
                    422,
                    'MIXED_TOTAL_INVALID'
                );
            }
        }

        $enteredAt = now();

        $paymentAt = $request->filled('payment_at')
            ? Carbon::parse($request->input('payment_at'))
            : $enteredAt;

        if ($paymentAt->isFuture()) {
            return ApiResponse::error(
                'Future payment date is not allowed.',
                422,
                'FUTURE_PAYMENT_NOT_ALLOWED'
            );
        }

        $createdPayment = null;
        $auditData = [];

        try {
            DB::transaction(function () use (
                $customerId,
                $received,
                $method,
                $cashTotal,
                $cardTotal,
                $phoneTotal,
                $paymentAt,
                $enteredAt,
                $note,
                $user,
                &$createdPayment,
                &$auditData
            ) {
                /** @var \App\Models\Credit $credit */
                $credit = Credit::query()
                    ->where('logicalref', $customerId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($credit->amount_local === null || $credit->paid_local === null) {
                    throw new \RuntimeException('Local debt information is missing.');
                }

                $totalLocal = (float) $credit->amount_local;
                $paidLocal = (float) $credit->paid_local;
                $remaining = round($totalLocal - $paidLocal, 2);

                if ($remaining <= 0.01) {
                    throw new \RuntimeException('Debt already closed.');
                }

                $maxExtra = 100;
                if ($received > $remaining + $maxExtra) {
                    throw new \RuntimeException('Overpayment is too high.');
                }

                $apply = min($received, $remaining);
                $change = round($received - $apply, 2);
                $newPaidLocal = round($paidLocal + $apply, 2);
                $newRemaining = round($totalLocal - $newPaidLocal, 2);

                if ($newRemaining < 0) {
                    $newRemaining = 0;
                }

                $backdated = $paymentAt->lt($enteredAt->copy()->startOfMinute());

                $credit->forceFill([
                    'paid_local' => number_format($newPaidLocal, 2, '.', ''),
                    'paid_updated_by' => $user->id,
                    'paid_updated_at' => $paymentAt,
                    'paid_note' => 'api_payment_created'
                        . ' | method=' . $method
                        . ' | received=' . number_format($received, 2, '.', '')
                        . ' | applied=' . number_format($apply, 2, '.', '')
                        . ' | change=' . number_format($change, 2, '.', '')
                        . ' | payment_at=' . $paymentAt->format('Y-m-d H:i:s'),
                ])->save();

                $createdPayment = CreditPayment::create([
                    'credit_logicalref' => (int) $credit->logicalref,

                    'customer_name' => mb_substr((string) $credit->name, 0, 255),
                    'customer_phone' => mb_substr((string) $credit->phone, 0, 50),
                    'customer_passport' => mb_substr((string) $credit->passport, 0, 50),
                    'customer_contract' => mb_substr((string) $credit->contract, 0, 50),
                    'branch' => mb_substr((string) $credit->branch, 0, 50),

                    'created_by' => (int) $user->id,
                    'created_by_name' => mb_substr((string) $user->full_name, 0, 255),
                    'created_by_email' => mb_substr((string) $user->email, 0, 255),
                    'created_by_phone' => mb_substr((string) $user->phonenumber, 0, 50),

                    'pay_amount' => number_format($received, 2, '.', ''),
                    'change_amount' => number_format($change, 2, '.', ''),
                    'method' => $method,
                    'cash_amount' => number_format($cashTotal, 2, '.', ''),
                    'card_amount' => number_format($cardTotal, 2, '.', ''),
                    'phone_amount' => number_format($phoneTotal, 2, '.', ''),

                    'old_amount_local' => number_format($remaining, 2, '.', ''),
                    'new_amount_local' => number_format($newRemaining, 2, '.', ''),
                    'old_paid_local' => number_format($paidLocal, 2, '.', ''),
                    'new_paid_local' => number_format($newPaidLocal, 2, '.', ''),

                    'note' => $note,
                    'created_at' => $paymentAt,
                ]);

                $createdPayment->load([
                    'correctedByUser:id,firstname,lastname',
                    'voidedByUser:id,firstname,lastname',
                ]);

                $auditData = [
                    'credit_logicalref' => (int) $credit->logicalref,
                    'customer_name' => $credit->name,
                    'customer_contract' => $credit->contract,
                    'method' => $method,
                    'received' => $received,
                    'applied' => $apply,
                    'change' => $change,
                    'cash_amount' => $cashTotal,
                    'card_amount' => $cardTotal,
                    'phone_amount' => $phoneTotal,
                    'payment_at' => $paymentAt->format('Y-m-d H:i:s'),
                    'entered_at' => $enteredAt->format('Y-m-d H:i:s'),
                    'backdated' => $backdated,
                ];
            });
        } catch (\Throwable $e) {
            return ApiResponse::error(
                $e instanceof \RuntimeException ? $e->getMessage() : 'Payment create failed.',
                422,
                'PAYMENT_CREATE_FAILED'
            );
        }

        $audit->log(
            action: 'api_payment_created',
            category: 'payment',
            subject: $createdPayment,
            oldValues: null,
            newValues: null,
            extra: $auditData,
            message: 'API payment created',
            isSuccess: true,
            severity: !empty($auditData['backdated']) ? 'warning' : 'info',
            isSuspicious: !empty($auditData['backdated'])
        );

        if (!empty($auditData['backdated'])) {
            $audit->alert(
                alertType: 'api_backdated_payment',
                riskLevel: 'high',
                message: 'Backdated payment recorded via API',
                meta: $auditData
            );
        }

        if (((float) ($auditData['received'] ?? 0)) >= 5000) {
            $audit->alert(
                alertType: 'api_high_amount_payment',
                riskLevel: 'medium',
                message: 'High amount payment detected via API',
                meta: $auditData
            );
        }

        return ApiResponse::success(
            new CreditPaymentResource($createdPayment),
            'Payment created successfully.',
            201
        );
    }

    public function void(Request $request, CreditPayment $payment)
    {
        $request->validate([
            'void_reason' => ['required', 'string', 'max:1000'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $audit = app(AuditLogger::class);

        $reason = trim((string) $request->input('void_reason'));
        $reason = preg_replace('/\s+/', ' ', $reason);

        $voidedPayment = null;
        $auditData = [];

        try {
            DB::transaction(function () use ($payment, $reason, $user, &$voidedPayment, &$auditData) {
                /** @var \App\Models\CreditPayment $p */
                $p = CreditPayment::query()
                    ->whereKey($payment->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($p->voided_at !== null) {
                    throw new \RuntimeException('Payment is already voided.');
                }

                /** @var \App\Models\Credit $credit */
                $credit = Credit::query()
                    ->where('logicalref', (int) $p->credit_logicalref)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($credit->amount_local === null || $credit->paid_local === null) {
                    throw new \RuntimeException('Local debt information is missing.');
                }

                $received = (float) $p->pay_amount;
                $change = (float) ($p->change_amount ?? 0);
                $applied = round(max($received - $change, 0), 2);

                $paidLocal = (float) $credit->paid_local;
                $newPaidLocal = round($paidLocal - $applied, 2);

                if ($newPaidLocal < 0) {
                    $newPaidLocal = 0;
                }

                $now = now();

                $credit->forceFill([
                    'paid_local' => number_format($newPaidLocal, 2, '.', ''),
                    'paid_updated_by' => (int) $user->id,
                    'paid_updated_at' => $now,
                    'paid_note' => 'api_voided=yes'
                        . ' | payment_id=' . $p->id
                        . ' | applied=' . number_format($applied, 2, '.', '')
                        . ' | reason=' . $reason,
                ])->save();

                $p->forceFill([
                    'voided_at' => $now,
                    'voided_by' => (int) $user->id,
                    'void_reason' => $reason,
                ])->save();

                $voidedPayment = $p->fresh([
                    'voidedByUser:id,firstname,lastname',
                    'correctedByUser:id,firstname,lastname',
                ]);

                $auditData = [
                    'payment_id' => $p->id,
                    'credit_logicalref' => (int) $credit->logicalref,
                    'customer_name' => $credit->name,
                    'customer_contract' => $credit->contract,
                    'applied' => $applied,
                    'void_reason' => $reason,
                ];
            });
        } catch (\Throwable $e) {
            return ApiResponse::error(
                $e instanceof \RuntimeException ? $e->getMessage() : 'Payment void failed.',
                422,
                'PAYMENT_VOID_FAILED'
            );
        }

        $audit->log(
            action: 'api_payment_voided',
            category: 'payment',
            subject: $voidedPayment,
            oldValues: null,
            newValues: null,
            extra: $auditData,
            message: 'API payment voided',
            isSuccess: true,
            severity: 'critical',
            isSuspicious: true
        );

        $audit->alert(
            alertType: 'api_payment_voided',
            riskLevel: 'critical',
            message: 'A payment was voided via API',
            meta: $auditData
        );

        return ApiResponse::success(
            new CreditPaymentResource($voidedPayment),
            'Payment voided successfully.'
        );
    }

    public function correct(Request $request, CreditPayment $payment)
    {
        $request->validate([
            'payment_at' => ['nullable', 'date'],
            'payment_method' => ['required', 'in:cash,card,mixed,phone'],
            'pay_amount' => ['required', 'numeric', 'min:0.01'],
            'cash_total' => ['nullable', 'numeric', 'min:0'],
            'card_total' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'reason' => ['nullable', 'string', 'max:300'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $audit = app(AuditLogger::class);

        $enteredAt = now();
        $paymentAt = $request->filled('payment_at')
            ? Carbon::parse($request->input('payment_at'))
            : $enteredAt;

        if ($paymentAt->isFuture()) {
            return ApiResponse::error(
                'Future payment date is not allowed.',
                422,
                'FUTURE_PAYMENT_NOT_ALLOWED'
            );
        }

        $method = (string) $request->input('payment_method');
        $received = round((float) $request->input('pay_amount'), 2);
        $cashTotal = round((float) $request->input('cash_total', 0), 2);
        $cardTotal = round((float) $request->input('card_total', 0), 2);
        $phoneTotal = 0.0;

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
                return ApiResponse::error(
                    'Mixed total must equal received amount.',
                    422,
                    'MIXED_TOTAL_INVALID'
                );
            }
        }

        $note = trim((string) $request->input('note', ''));
        $note = preg_replace('/\s+/', ' ', $note);
        $note = $note !== '' ? $note : null;

        $reason = trim((string) $request->input('reason', ''));
        $reason = preg_replace('/\s+/', ' ', $reason);
        $reason = $reason !== '' ? $reason : null;

        $newPayment = null;
        $auditData = [];

        try {
            DB::transaction(function () use (
                $payment,
                $user,
                $enteredAt,
                $paymentAt,
                $method,
                $received,
                $cashTotal,
                $cardTotal,
                $phoneTotal,
                $note,
                $reason,
                &$newPayment,
                &$auditData
            ) {
                /** @var \App\Models\CreditPayment $oldPayment */
                $oldPayment = CreditPayment::query()
                    ->lockForUpdate()
                    ->findOrFail($payment->id);

                if ($oldPayment->voided_at) {
                    throw new \RuntimeException('Payment is already voided.');
                }

                /** @var \App\Models\Credit $credit */
                $credit = Credit::query()
                    ->where('logicalref', (int) $oldPayment->credit_logicalref)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($credit->amount_local === null || $credit->paid_local === null) {
                    throw new \RuntimeException('Local debt information is missing.');
                }

                $totalLocal = (float) $credit->amount_local;
                $paidLocal = (float) $credit->paid_local;

                $oldReceived = (float) $oldPayment->pay_amount;
                $oldChange = (float) ($oldPayment->change_amount ?? 0);
                $oldApplied = round(max($oldReceived - $oldChange, 0), 2);

                $paidAfterVoid = round($paidLocal - $oldApplied, 2);
                if ($paidAfterVoid < 0) {
                    $paidAfterVoid = 0;
                }

                $oldPayment->forceFill([
                    'voided_at' => $enteredAt,
                    'voided_by' => (int) $user->id,
                    'void_reason' => $reason ?: 'Corrected via API',
                ])->save();

                $credit->forceFill([
                    'paid_local' => number_format($paidAfterVoid, 2, '.', ''),
                    'paid_updated_by' => (int) $user->id,
                    'paid_updated_at' => $enteredAt,
                    'paid_note' => 'api_correct_void_old | old_payment_id=' . $oldPayment->id,
                ])->save();

                $remaining = round($totalLocal - $paidAfterVoid, 2);
                if ($remaining <= 0.01) {
                    throw new \RuntimeException('Debt already closed after reverting old payment.');
                }

                $maxExtra = 100;
                if ($received > $remaining + $maxExtra) {
                    throw new \RuntimeException('Overpayment is too high.');
                }

                $apply = min($received, $remaining);
                $change = round($received - $apply, 2);
                $newPaidLocal = round($paidAfterVoid + $apply, 2);
                $newRemaining = round($totalLocal - $newPaidLocal, 2);

                if ($newRemaining < 0) {
                    $newRemaining = 0;
                }

                $credit->forceFill([
                    'paid_local' => number_format($newPaidLocal, 2, '.', ''),
                    'paid_updated_by' => (int) $user->id,
                    'paid_updated_at' => $paymentAt,
                    'paid_note' => 'api_payment_corrected'
                        . ' | corrected_from_payment_id=' . $oldPayment->id
                        . ' | method=' . $method,
                ])->save();

                $newPayment = CreditPayment::create([
                    'credit_logicalref' => (int) $credit->logicalref,

                    'customer_name' => mb_substr((string) $credit->name, 0, 255),
                    'customer_phone' => mb_substr((string) $credit->phone, 0, 50),
                    'customer_passport' => mb_substr((string) $credit->passport, 0, 50),
                    'customer_contract' => mb_substr((string) $credit->contract, 0, 50),
                    'branch' => mb_substr((string) $credit->branch, 0, 50),

                    'created_by' => $oldPayment->created_by,
                    'created_by_name' => mb_substr((string) ($oldPayment->created_by_name ?? 'N/A'), 0, 255),
                    'created_by_email' => mb_substr((string) ($oldPayment->created_by_email ?? ''), 0, 255),
                    'created_by_phone' => mb_substr((string) ($oldPayment->created_by_phone ?? ''), 0, 50),

                    'pay_amount' => number_format($received, 2, '.', ''),
                    'change_amount' => number_format($change, 2, '.', ''),
                    'method' => $method,
                    'cash_amount' => number_format($cashTotal, 2, '.', ''),
                    'card_amount' => number_format($cardTotal, 2, '.', ''),
                    'phone_amount' => number_format($phoneTotal, 2, '.', ''),

                    'old_amount_local' => number_format($remaining, 2, '.', ''),
                    'new_amount_local' => number_format($newRemaining, 2, '.', ''),
                    'old_paid_local' => number_format($paidAfterVoid, 2, '.', ''),
                    'new_paid_local' => number_format($newPaidLocal, 2, '.', ''),

                    'note' => $note,
                    'created_at' => $paymentAt,

                    'corrected_by' => (int) $user->id,
                    'corrected_at' => $enteredAt,
                    'correct_reason' => $reason,
                    'corrected_from_payment_id' => $oldPayment->id,
                ]);

                $newPayment->load([
                    'correctedByUser:id,firstname,lastname',
                    'voidedByUser:id,firstname,lastname',
                ]);

                $auditData = [
                    'old_payment_id' => $oldPayment->id,
                    'new_payment_id' => $newPayment->id,
                    'credit_logicalref' => (int) $credit->logicalref,
                    'customer_name' => $credit->name,
                    'customer_contract' => $credit->contract,
                    'method' => $method,
                    'received' => $received,
                    'reason' => $reason,
                ];
            });
        } catch (\Throwable $e) {
            return ApiResponse::error(
                $e instanceof \RuntimeException ? $e->getMessage() : 'Payment correction failed.',
                422,
                'PAYMENT_CORRECT_FAILED'
            );
        }

        $audit->log(
            action: 'api_payment_corrected',
            category: 'payment',
            subject: $newPayment,
            oldValues: null,
            newValues: null,
            extra: $auditData,
            message: 'API payment corrected',
            isSuccess: true,
            severity: 'critical',
            isSuspicious: true
        );

        $audit->alert(
            alertType: 'api_payment_corrected',
            riskLevel: 'critical',
            message: 'A payment was corrected via API',
            meta: $auditData
        );

        return ApiResponse::success(
            new CreditPaymentResource($newPayment),
            'Payment corrected successfully.'
        );
    }
}