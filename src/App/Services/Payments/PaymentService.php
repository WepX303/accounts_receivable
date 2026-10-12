<?php

namespace App\Services\Payments;

use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Takes, voids and corrects payments. Every channel goes through here, so the
 * money rules, the audit trail and the alerts cannot drift apart between them.
 */
class PaymentService
{
    // maximum permitted change
    private const MAX_EXTRA = 100;

    private const HIGH_AMOUNT = 5000;

    public function __construct(private AuditLogger $audit)
    {
    }

    /**
     * Checks everything about the input that can be judged without the credit.
     *
     * @throws PaymentException
     */
    public function validate(CreatePaymentData $data): void
    {
        $this->normalize($data);
    }

    /**
     * @throws PaymentException
     */
    public function create(User $user, CreatePaymentData $data): CreditPayment
    {
        [$method, $receiverPhoneNumber, $received, $cashTotal, $cardTotal, $phoneTotal, $note] = $this->normalize($data);

        $customerId = $data->creditSourceId;
        $userId = (int) $user->id;
        $clientRequestId = $data->clientRequestId;

        $enteredAt = now();
        $paymentAtProvided = $data->paymentAt !== null;
        $now = $data->paymentAt ?? $enteredAt;

        // Future date not allowed
        if ($now->isFuture()) {
            throw new PaymentException('future_payment_date', __('validations/validations.payments.future_payment_date_not_allowed'));
        }

        $backdated = $paymentAtProvided && $now->lt($enteredAt->copy()->startOfMinute());

        try {
            [$payment, $auditData] = DB::transaction(function () use ($customerId, $received, $userId, $user, $now, $enteredAt, $backdated, $method, $cashTotal, $cardTotal, $phoneTotal, $note, $receiverPhoneNumber, $clientRequestId) {
                /** @var \App\Models\Credit $c */
                $c = Credit::query()
                    ->where('active', true)
                    ->where('source_id', $customerId)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Looked up under the credit lock, so a request repeated while the
                // first is still running waits here and then finds its payment.
                if ($clientRequestId !== null) {
                    $existing = CreditPayment::withoutGlobalScopes()
                        ->where('client_request_id', $clientRequestId)
                        ->first();

                    if ($existing) {
                        if ((int) $existing->credit_source_id !== $customerId || (int) $existing->created_by !== $userId) {
                            throw new PaymentException('idempotency_key_reused', __('api.idempotency_key_reused'));
                        }

                        return [$existing, null];
                    }
                }

                // ONLY LOCAL
                if ($c->amount_local === null || $c->paid_local === null) {
                    throw new PaymentException('local_debt_missing', __('validations/validations.payments.local_debt_missing'));
                }

                $totalLocal = (float) $c->amount_local; // TOTAL DEBT
                $paidLocal = (float) $c->paid_local;  // TOTAL PAID

                // If closed, receive payment (including threshold)
                if ($paidLocal >= $totalLocal - 0.01) {
                    throw new PaymentException('debt_closed', __('validations/validations.payments.debt_closed_paid_ge_total'));
                }

                $remaining = $totalLocal - $paidLocal;
                if ($remaining < 0) {
                    $remaining = 0;
                }

                if ($remaining <= 0.01) {
                    throw new PaymentException('debt_closed', __('validations/validations.payments.debt_closed_remaining_zero'));
                }

                // Overpayment limit (overpayment guard)
                $maxExtra = self::MAX_EXTRA;
                if ($received > $remaining + $maxExtra) {
                    throw new PaymentException(
                        'overpayment_too_high',
                        __('validations/validations.payments.overpayment_too_high') . number_format($maxExtra, 2)
                    );
                }

                // Change: the amount to be applied to the debt remaining
                $apply = min($received, $remaining);
                $change = $received - $apply;

                // Mixed transaction-internal guarantee
                if ($method === 'mixed') {
                    if (abs(($cashTotal + $cardTotal) - $received) > 0.01) {
                        throw new PaymentException('mixed_sum_must_equal', __('validations/validations.payments.mixed_sum_must_equal_tx'));
                    }
                }

                $oldRemaining = $remaining;
                $oldPaidLocal = $paidLocal;

                $newPaidLocal = $paidLocal + $apply;

                $newRemaining = $totalLocal - $newPaidLocal;
                if ($newRemaining < 0) {
                    $newRemaining = 0;
                }

                // Audit note
                $detail = [

                    'backdated=' . ($backdated ? 'yes' : 'no'),
                    'payment_at=' . $now->format('Y-m-d H:i:s'),
                    'entered_at=' . $enteredAt->format('Y-m-d H:i:s'),

                    'method=' . $method,

                    'received=' . Money::format($received),
                    'applied=' . Money::format($apply),
                    'change=' . Money::format($change),

                    'cash=' . Money::format($cashTotal),
                    'card=' . Money::format($cardTotal),
                    'phone=' . Money::format($phoneTotal),

                    'total_local=' . Money::format($totalLocal),

                    'old_paid_local=' . Money::format($oldPaidLocal),
                    'new_paid_local=' . Money::format($newPaidLocal),

                    'old_remaining=' . Money::format($oldRemaining),
                    'new_remaining=' . Money::format($newRemaining),
                ];

                $finalNote = implode(' | ', $detail);
                if ($note !== '') {
                    $finalNote .= ' | note=' . $note;
                }

                // Only paid_local increases
                $c->forceFill([
                    'paid_local' => Money::format($newPaidLocal),
                    'paid_updated_by' => $userId,
                    'paid_updated_at' => $now,
                    'paid_note' => $finalNote,
                ])->save();

                // History:
                // pay_amount = received, change_amount = change
                // old_amount_local/new_amount_local = remaining (log)
                $payment = CreditPayment::create([
                    'credit_source_id' => (int) $c->source_id,
                    'credit_logicalref' => (int) $c->logicalref,
                    // SNAPSHOT (the owner should be clear from the payment record)
                    'customer_name' => mb_substr((string) $c->name, 0, 255),
                    'customer_phone' => mb_substr((string) $c->phone, 0, 50),
                    'customer_passport' => mb_substr((string) $c->passport, 0, 50),
                    'customer_contract' => mb_substr((string) $c->contract, 0, 50),
                    'branch' => mb_substr((string) $c->branch, 0, 50),

                    // User snapshot
                    'created_by' => $userId,
                    'created_by_name' => mb_substr((string) $user->full_name, 0, 255),
                    'created_by_email' => mb_substr((string) $user->email, 0, 255),
                    'created_by_phone' => mb_substr((string) $user->phonenumber, 0, 50),

                    'pay_amount' => Money::format($received),
                    'change_amount' => Money::format($change),

                    'method' => $method,
                    'cash_amount' => Money::format($cashTotal),
                    'card_amount' => Money::format($cardTotal),
                    'phone_amount' => Money::format($phoneTotal),
                    'receiver_phone_number' => $receiverPhoneNumber,

                    'old_amount_local' => Money::format($oldRemaining),
                    'new_amount_local' => Money::format($newRemaining),

                    'old_paid_local' => Money::format($oldPaidLocal),
                    'new_paid_local' => Money::format($newPaidLocal),

                    'note' => $note !== '' ? $note : null,
                    'created_at' => $now,

                    // Named only when there is one, so the panel keeps working
                    // on a database the column has not reached yet.
                    ...($clientRequestId !== null ? ['client_request_id' => $clientRequestId] : []),
                ]);

                return [$payment, [
                    'credit' => $c,
                    'old_values' => [
                        'paid_local' => $oldPaidLocal,
                        'remaining' => $oldRemaining,
                    ],
                    'new_values' => [
                        'paid_local' => $newPaidLocal,
                        'remaining' => $newRemaining,
                    ],
                    'extra' => [
                        'credit_source_id' => (int) $c->source_id,
                        'credit_logicalref' => (int) $c->logicalref,
                        'customer_name' => $c->name,
                        'customer_contract' => $c->contract,
                        'method' => $method,
                        'received' => $received,
                        'applied' => $apply,
                        'change' => $change,
                        'cash_amount' => $cashTotal,
                        'card_amount' => $cardTotal,
                        'phone_amount' => $phoneTotal,
                        'receiver_phone_number' => $receiverPhoneNumber,
                        'payment_at' => $now->format('Y-m-d H:i:s'),
                        'entered_at' => $enteredAt->format('Y-m-d H:i:s'),
                        'backdated' => $backdated,
                    ],
                ]];
            });
        } catch (\Throwable $e) {
            $msg = $e instanceof \RuntimeException
                ? $e->getMessage()
                : __('validations/validations.payments.payment_save_failed');

            $this->audit->log(
                action: 'payment_create_failed',
                category: 'payment',
                subject: null,
                oldValues: null,
                newValues: null,
                extra: [
                    'customer_id' => $customerId,
                    'payment_method' => $method,
                    'pay_amount' => $received,
                    'payment_at' => $data->paymentAt?->format('Y-m-d\TH:i'),
                    'error' => $msg,
                ],
                message: 'Customer payment create failed',
                isSuccess: false,
                severity: 'warning',
                isSuspicious: true
            );

            throw $this->refused($e, $msg);
        }

        // A repeated submission: the payment was booked and logged the first time.
        if ($auditData === null) {
            return $payment;
        }

        $this->audit->log(
            action: 'payment_created',
            category: 'payment',
            subject: $auditData['credit'],
            oldValues: $auditData['old_values'],
            newValues: $auditData['new_values'],
            extra: $auditData['extra'],
            message: 'Customer payment created',
            isSuccess: true,
            severity: ! empty($auditData['extra']['backdated']) ? 'warning' : 'info',
            isSuspicious: ! empty($auditData['extra']['backdated'])
        );

        if (! empty($auditData['extra']['backdated'])) {
            $this->audit->alert(
                alertType: 'backdated_payment',
                riskLevel: 'high',
                message: 'Backdated payment recorded',
                meta: $auditData['extra']
            );
        }

        if (((float) ($auditData['extra']['received'] ?? 0)) >= self::HIGH_AMOUNT) {
            $this->audit->alert(
                alertType: 'high_amount_payment',
                riskLevel: 'medium',
                message: 'High amount payment detected',
                meta: $auditData['extra']
            );
        }

        return $payment;
    }

    /**
     * @throws PaymentException
     */
    public function void(User $user, CreditPayment $payment, ?string $reason): CreditPayment
    {
        if (! $user->canVoidPayments()) {
            throw new PaymentException('forbidden', __('validations/validations.payment_void.admin_only'));
        }

        $reason = trim((string) $reason);
        $reason = preg_replace('/\s+/', ' ', $reason);

        if ($reason === '') {
            throw new PaymentException('void_reason_required', __('validations/validations.payment_void.void_reason_required'));
        }

        try {
            [$voided, $auditData] = DB::transaction(function () use ($payment, $reason, $user) {

                /** @var CreditPayment $p */
                $p = CreditPayment::query()
                    ->whereKey($payment->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($p->voided_at !== null) {
                    throw new PaymentException('already_voided', __('validations/validations.payment_void.already_voided'));
                }

                /** @var Credit $c */
                $c = Credit::query()
                    ->where('active', true)
                    ->where('source_id', (int) $p->credit_source_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($c->amount_local === null || $c->paid_local === null) {
                    throw new PaymentException('local_debt_missing', __('validations/validations.payment_void.local_debt_missing'));
                }

                $received = (float) $p->pay_amount;
                $change = (float) ($p->change_amount ?? 0);
                $applied = $received - $change;
                if ($applied < 0) {
                    $applied = 0;
                }
                $applied = round($applied, 2);

                $paidLocal = (float) $c->paid_local;
                $newPaidLocal = $paidLocal - $applied;
                if ($newPaidLocal < 0) {
                    $newPaidLocal = 0;
                }

                // Credit update (paid_local is refunded)
                $c->forceFill([
                    'paid_local' => number_format(round($newPaidLocal, 2), 2, '.', ''),
                    'paid_updated_by' => (int) $user->id,
                    'paid_updated_at' => now(),
                    'paid_note' => 'voided=yes'
                        . ' | voided_payment_id=' . $p->id
                        . ' | voided_applied=' . number_format($applied, 2, '.', '')
                        . ' | void_reason=' . $reason
                        . ' | voided_by=' . $user->full_name
                        . ' | voided_at=' . now()->format('Y-m-d H:i:s'),
                ])->save();

                $auditData = [
                    'payment' => $p,
                    'credit' => $c,
                    'old_values' => [
                        'payment_id' => $p->id,
                        'voided_at' => null,
                        'paid_local_before' => $paidLocal,
                    ],
                    'new_values' => [
                        'voided_at' => now()->format('Y-m-d H:i:s'),
                        'voided_by' => $user->id,
                        'paid_local_after' => $newPaidLocal,
                    ],
                    'extra' => [
                        'credit_source_id' => (int) $c->source_id,
                        'credit_logicalref' => (int) $c->logicalref,
                        'applied' => $applied,
                        'void_reason' => $reason,
                        'customer_name' => $c->name,
                        'customer_contract' => $c->contract,
                    ],
                ];

                // Payment void flag
                $p->forceFill([
                    'voided_at' => now(),
                    'voided_by' => (int) $user->id,
                    'void_reason' => $reason,
                ])->save();

                return [$p, $auditData];
            });
        } catch (\Throwable $e) {
            $msg = $e instanceof \RuntimeException
                ? $e->getMessage()
                : __('validations/validations.payment_void.void_failed');

            $this->audit->log(
                action: 'payment_void_failed',
                category: 'payment',
                subject: $payment,
                oldValues: null,
                newValues: null,
                extra: [
                    'payment_id' => $payment->id ?? null,
                    'void_reason' => $reason,
                    'error' => $msg,
                ],
                message: 'Payment void failed',
                isSuccess: false,
                severity: 'critical',
                isSuspicious: true
            );

            throw $this->refused($e, $msg);
        }

        $this->audit->log(
            action: 'payment_voided',
            category: 'payment',
            subject: $auditData['payment'],
            oldValues: $auditData['old_values'],
            newValues: $auditData['new_values'],
            extra: $auditData['extra'],
            message: 'Payment voided',
            isSuccess: true,
            severity: 'critical',
            isSuspicious: true
        );

        $this->audit->alert(
            alertType: 'payment_voided',
            riskLevel: 'critical',
            message: 'A payment was voided',
            meta: $auditData['extra']
        );

        return $voided;
    }

    /**
     * The checks a correction must pass before its form is even looked at.
     *
     * @throws PaymentException
     */
    public function assertCanCorrect(User $user, CreditPayment $payment): void
    {
        if (! $user->canVoidPayments()) {
            throw new PaymentException('forbidden', __('validations/validations.payment_void.admin_only'));
        }

        // Cannot correct already voided
        if ($payment->voided_at) {
            throw new PaymentException('already_voided', __('validations/validations.payment_correct.already_voided'));
        }
    }

    /**
     * Voids the payment and books a new one in its place, in one transaction.
     * Returns the new payment.
     *
     * @throws PaymentException
     */
    public function correct(User $user, CreditPayment $payment, CorrectPaymentData $data): CreditPayment
    {
        $this->assertCanCorrect($user, $payment);

        $enteredAt = now();
        $now = $data->paymentAt ?? $enteredAt;

        if ($now->isFuture()) {
            throw new PaymentException('future_payment_date', __('validations/validations.payment_correct.future_payment_date_not_allowed'));
        }

        $method = $data->method;
        $received = $data->received;
        $cashTotal = $data->cashTotal;
        $cardTotal = $data->cardTotal;
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
                throw new PaymentException('mixed_sum_must_equal', __('validations/validations.payment_correct.mixed_sum_must_equal'));
            }
        }

        $note = trim($data->note);
        $note = preg_replace('/\s+/', ' ', $note);

        $reason = trim($data->reason);
        $reason = preg_replace('/\s+/', ' ', $reason);

        try {
            [$newPayment, $auditData] = DB::transaction(function () use ($payment, $user, $enteredAt, $now, $method, $received, $cashTotal, $cardTotal, $phoneTotal, $note, $reason) {

                // lock payment row
                $p = CreditPayment::query()->lockForUpdate()->findOrFail($payment->id);

                if ($p->voided_at) {
                    throw new PaymentException('already_voided', __('validations/validations.payment_correct.already_voided'));
                }

                // lock credit
                $c = Credit::query()
                    ->where('active', true)
                    ->where('source_id', (int) $p->credit_source_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($c->amount_local === null || $c->paid_local === null) {
                    throw new PaymentException('local_debt_missing', __('validations/validations.payment_correct.local_debt_missing'));
                }

                $totalLocal = (float) $c->amount_local;
                $paidLocal = (float) $c->paid_local;

                // ---------- STEP 1: VOID OLD PAYMENT ----------
                // Old apply = pay - change
                $oldReceived = (float) $p->pay_amount;
                $oldChange = (float) ($p->change_amount ?? 0);
                $oldApplied = $oldReceived - $oldChange;
                if ($oldApplied < 0) {
                    $oldApplied = 0;
                }

                // revert paid_local by old applied
                $paidAfterVoid = $paidLocal - $oldApplied;
                if ($paidAfterVoid < 0) {
                    $paidAfterVoid = 0;
                }

                $p->forceFill([
                    'voided_at' => $enteredAt,
                    'voided_by' => (int) $user->id,
                    'void_reason' => $reason !== '' ? $reason : __('validations/validations.payment_correct.corrected_default_reason'),
                ])->save();

                $c->forceFill([
                    'paid_local' => Money::format($paidAfterVoid),
                    'paid_updated_by' => (int) $user->id,
                    'paid_updated_at' => $enteredAt,
                    'paid_note' => 'correct:void_old | old_payment_id=' . $p->id . ' | old_applied=' . Money::format($oldApplied),
                ])->save();

                // ---------- STEP 2: CREATE NEW PAYMENT ----------
                $remaining = $totalLocal - $paidAfterVoid;
                if ($remaining < 0) {
                    $remaining = 0;
                }

                // Same rules as create()
                $remaining = round($remaining, 2);
                $received = round($received, 2);

                if ($remaining <= 0.01) {
                    throw new PaymentException('debt_closed', __('validations/validations.payment_correct.debt_closed_use_void_only'));
                }

                $maxExtra = self::MAX_EXTRA;
                if ($received > $remaining + $maxExtra) {
                    throw new PaymentException(
                        'overpayment_too_high',
                        __('validations/validations.payment_correct.overpayment_too_high', [
                            'max' => number_format($maxExtra, 2),
                        ])
                    );
                }

                $apply = min($received, $remaining);
                $change = $received - $apply;

                $newPaidLocal = $paidAfterVoid + $apply;
                $newRemaining = $totalLocal - $newPaidLocal;
                if ($newRemaining < 0) {
                    $newRemaining = 0;
                }

                $detail = [
                    'corrected=yes',
                    'corrected_from_payment_id=' . $p->id,
                    'payment_at=' . $now->format('Y-m-d H:i:s'),
                    'entered_at=' . $enteredAt->format('Y-m-d H:i:s'),
                    'method=' . $method,
                    'received=' . Money::format($received),
                    'applied=' . Money::format($apply),
                    'change=' . Money::format($change),
                    'cash=' . Money::format($cashTotal),
                    'card=' . Money::format($cardTotal),
                    'phone=' . Money::format($phoneTotal),
                    'total_local=' . Money::format($totalLocal),
                    'old_paid_local=' . Money::format($paidAfterVoid),
                    'new_paid_local=' . Money::format($newPaidLocal),
                    'old_remaining=' . Money::format($remaining),
                    'new_remaining=' . Money::format($newRemaining),
                ];

                $finalNote = implode(' | ', $detail);
                if ($note !== '') {
                    $finalNote .= ' | note=' . $note;
                }

                $c->forceFill([
                    'paid_local' => Money::format($newPaidLocal),
                    'paid_updated_by' => (int) $user->id,
                    'paid_updated_at' => $now,
                    'paid_note' => $finalNote,
                ])->save();

                $newPayment = CreditPayment::create([

                    'credit_source_id' => (int) $c->source_id,
                    'credit_logicalref' => (int) $c->logicalref,

                    'customer_name' => mb_substr((string) $c->name, 0, 255),
                    'customer_phone' => mb_substr((string) $c->phone, 0, 50),
                    'customer_passport' => mb_substr((string) $c->passport, 0, 50),
                    'customer_contract' => mb_substr((string) $c->contract, 0, 50),
                    'branch' => mb_substr((string) $c->branch, 0, 50),

                    // PAYMENT RECEIVED BY: ORIGINAL CASHIER TO REMAIN
                    'created_by' => $p->created_by,
                    'created_by_name' => mb_substr((string) ($p->created_by_name ?? 'N/A'), 0, 255),
                    'created_by_email' => mb_substr((string) ($p->created_by_email ?? ''), 0, 255),
                    'created_by_phone' => mb_substr((string) ($p->created_by_phone ?? ''), 0, 50),

                    'pay_amount' => Money::format($received),
                    'change_amount' => Money::format($change),

                    'method' => $method,
                    'cash_amount' => Money::format($cashTotal),
                    'card_amount' => Money::format($cardTotal),
                    'phone_amount' => Money::format($phoneTotal),

                    'old_amount_local' => Money::format($remaining),
                    'new_amount_local' => Money::format($newRemaining),

                    'old_paid_local' => Money::format($paidAfterVoid),
                    'new_paid_local' => Money::format($newPaidLocal),

                    'note' => $note !== '' ? $note : null,
                    'created_at' => $now,

                    // EDITED BY: ADMIN/OPERATOR
                    'corrected_by' => (int) $user->id,
                    'corrected_at' => $enteredAt,
                    'correct_reason' => $reason !== '' ? $reason : null,

                    'corrected_from_payment_id' => $p->id,
                ]);

                return [$newPayment, [
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
                        'credit_source_id' => (int) $c->source_id,
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
                ]];
            });
        } catch (\Throwable $e) {
            $msg = $e instanceof \RuntimeException
                ? $e->getMessage()
                : __('validations/validations.payment_correct.correction_failed');

            $this->audit->log(
                action: 'payment_correct_failed',
                category: 'payment',
                subject: $payment,
                oldValues: null,
                newValues: null,
                extra: [
                    'payment_id' => $payment->id ?? null,
                    'payment_method' => $method,
                    'pay_amount' => $received,
                    'reason' => $reason,
                    'error' => $msg,
                ],
                message: 'Payment correction failed',
                isSuccess: false,
                severity: 'critical',
                isSuspicious: true
            );

            throw $this->refused($e, $msg);
        }

        $this->audit->log(
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

        $this->audit->alert(
            alertType: 'payment_corrected',
            riskLevel: 'critical',
            message: 'A payment was corrected',
            meta: $auditData['extra']
        );

        return $newPayment;
    }

    /**
     * Returns method, receiver phone, received, cash, card, phone and note, in that order.
     *
     * @return array{0: string, 1: ?string, 2: float, 3: float, 4: float, 5: float, 6: string}
     *
     * @throws PaymentException
     */
    private function normalize(CreatePaymentData $data): array
    {
        $method = $data->method;
        if (! in_array($method, ['cash', 'card', 'mixed', 'phone'], true)) {
            throw new PaymentException('method_invalid', __('validations/validations.payments.method_invalid'));
        }

        $receiverPhoneNumber = null;

        if ($method === 'phone') {
            $receiverPhoneNumber = trim((string) $data->receiverPhoneNumber);
            $receiverPhoneNumber = preg_replace('/\s+/', '', $receiverPhoneNumber);

            if ($receiverPhoneNumber === '') {
                throw new PaymentException('receiver_phone_number_required', __('pages/payments.form.receiver_phone_number_required'));
            }

            if (! preg_match('/^[0-9]+$/', $receiverPhoneNumber)) {
                throw new PaymentException('receiver_phone_number_digits', __('pages/payments.form.receiver_phone_number_digits'));
            }
        }

        // Payment Amount = the money given by the customer (RECEIVED)
        $received = $data->received;
        if ($received <= 0) {
            throw new PaymentException('pay_amount_zero', __('validations/validations.payments.pay_amount_zero'));
        }

        $note = trim($data->note);
        $note = preg_replace('/\s+/', ' ', $note);

        $cashTotal = $data->cashTotal;
        $cardTotal = $data->cardTotal;
        $phoneTotal = 0.0;

        // Cash/card/mixed/phone totals must match received
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

            if ($cashTotal < 0 || $cardTotal < 0) {
                throw new PaymentException('mixed_negative', __('validations/validations.payments.mixed_negative'));
            }

            if (abs(($cashTotal + $cardTotal) - $received) > 0.01) {
                throw new PaymentException('mixed_sum_must_equal', __('validations/validations.payments.mixed_sum_must_equal'));
            }
        }

        return [$method, $receiverPhoneNumber, $received, $cashTotal, $cardTotal, $phoneTotal, $note];
    }

    /**
     * What to hand back to the caller for a failure inside the transaction.
     */
    private function refused(\Throwable $e, string $message): PaymentException
    {
        if ($e instanceof PaymentException) {
            return $e;
        }

        return new PaymentException($e instanceof ModelNotFoundException ? 'not_found' : 'failed', $message);
    }
}
