<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\CreditPayment;
use App\Services\Payments\CorrectPaymentData;
use App\Services\Payments\Money;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentCorrectController extends Controller
{
    public function __invoke(Request $request, CreditPayment $payment, PaymentService $payments)
    {

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            return back()->with('warning', __('validations/validations.payment_void.admin_only'));
        }

        try {
            $payments->assertCanCorrect($user, $payment);
        } catch (PaymentException $e) {
            return back()->with('warning', $e->getMessage());
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

        try {
            $payments->correct($user, $payment, new CorrectPaymentData(
                method: $data['payment_method'],
                received: Money::parse($data['pay_amount']),
                cashTotal: Money::parse($data['cash_total'] ?? 0),
                cardTotal: Money::parse($data['card_total'] ?? 0),
                note: (string) ($data['note'] ?? ''),
                reason: (string) ($data['reason'] ?? ''),
                paymentAt: ! empty($data['payment_at']) ? Carbon::parse($data['payment_at']) : null,
            ));
        } catch (PaymentException $e) {
            return back()->with('warning', $e->getMessage())->withInput();
        }

        return back()->with('success', __('validations/validations.payment_correct.corrected_success'));
    }
}
