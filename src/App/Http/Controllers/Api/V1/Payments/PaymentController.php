<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreditResource;
use App\Http\Resources\PaymentResource;
use App\Models\Credit;
use App\Models\CreditPayment;
use App\Services\Payments\CorrectPaymentData;
use App\Services\Payments\CreatePaymentData;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // Plain decimals only: "numeric" alone would also let through exponents such as 1e9999.
    private const AMOUNT = 'regex:/^\d{1,9}(\.\d{1,2})?$/';

    public function store(Request $request, PaymentService $payments)
    {
        // Without a key a request repeated over a bad connection would be booked twice.
        $request->merge(['idempotency_key' => $request->header('Idempotency-Key')]);

        $data = $request->validate([
            'idempotency_key' => 'required|string|min:16|max:64|regex:/^[A-Za-z0-9_-]+$/',
            'credit_id' => 'required|integer|min:1',
            'method' => 'required|string|in:cash,card,mixed,phone',
            'amount' => ['required', self::AMOUNT],
            'cash_amount' => ['nullable', self::AMOUNT],
            'card_amount' => ['nullable', self::AMOUNT],
            'receiver_phone_number' => 'nullable|string|max:30',
            'note' => 'nullable|string|max:500',
            'paid_at' => 'nullable|date',
        ]);

        try {
            $payment = $payments->create($request->user(), new CreatePaymentData(
                creditSourceId: (int) $data['credit_id'],
                method: $data['method'],
                received: round((float) $data['amount'], 2),
                cashTotal: round((float) ($data['cash_amount'] ?? 0), 2),
                cardTotal: round((float) ($data['card_amount'] ?? 0), 2),
                receiverPhoneNumber: $data['receiver_phone_number'] ?? null,
                note: (string) ($data['note'] ?? ''),
                paymentAt: $this->localTime($data['paid_at'] ?? null),
                clientRequestId: $data['idempotency_key'],
            ));
        } catch (PaymentException $e) {
            return $this->refused($e);
        }

        return $this->booked($payment, $payment->wasRecentlyCreated ? 201 : 200);
    }

    public function void(Request $request, CreditPayment $payment, PaymentService $payments)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:300',
        ]);

        try {
            $voided = $payments->void($request->user(), $payment, $data['reason']);
        } catch (PaymentException $e) {
            return $this->refused($e);
        }

        return $this->booked($voided);
    }

    public function correct(Request $request, CreditPayment $payment, PaymentService $payments)
    {
        $data = $request->validate([
            'method' => 'required|string|in:cash,card,mixed,phone',
            'amount' => ['required', self::AMOUNT],
            'cash_amount' => ['nullable', self::AMOUNT],
            'card_amount' => ['nullable', self::AMOUNT],
            'note' => 'nullable|string|max:500',
            'reason' => 'nullable|string|max:300',
            'paid_at' => 'nullable|date',
        ]);

        try {
            $new = $payments->correct($request->user(), $payment, new CorrectPaymentData(
                method: $data['method'],
                received: round((float) $data['amount'], 2),
                cashTotal: round((float) ($data['cash_amount'] ?? 0), 2),
                cardTotal: round((float) ($data['card_amount'] ?? 0), 2),
                note: (string) ($data['note'] ?? ''),
                reason: (string) ($data['reason'] ?? ''),
                paymentAt: $this->localTime($data['paid_at'] ?? null),
            ));
        } catch (PaymentException $e) {
            return $this->refused($e);
        }

        return $this->booked($new, 201);
    }

    /**
     * The payment together with the credit as it stands now, so the app can
     * refresh the debt without asking again.
     */
    private function booked(CreditPayment $payment, int $status = 200): JsonResponse
    {
        $payment->load(['createdByUser:id,firstname,lastname', 'correctedByUser:id,firstname,lastname', 'voidedByUser:id,firstname,lastname']);

        return ApiResponse::success([
            'payment' => (new PaymentResource($payment))->resolve(),
            'credit' => (new CreditResource(Credit::query()->findOrFail($payment->credit_source_id)))->resolve(),
        ], null, $status);
    }

    private function refused(PaymentException $e): JsonResponse
    {
        return match ($e->errorCode) {
            'forbidden' => ApiResponse::error(__('api.forbidden'), 403, 'forbidden'),
            'not_found' => ApiResponse::error(__('api.not_found'), 404, 'not_found'),
            // The message of an unexpected failure can be a database error; it stays in the audit log.
            'failed' => ApiResponse::error(__('api.server_error'), 500, 'server_error'),
            'idempotency_key_reused' => ApiResponse::error($e->getMessage(), 409, $e->errorCode),
            default => ApiResponse::error($e->getMessage(), 422, $e->errorCode),
        };
    }

    /**
     * The client sends an instant with its offset; the books are kept in the application's time zone.
     */
    private function localTime(?string $value): ?Carbon
    {
        return $value === null ? null : Carbon::parse($value)->setTimezone(config('app.timezone'));
    }
}
