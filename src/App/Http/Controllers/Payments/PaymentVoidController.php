<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\CreditPayment;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentVoidController extends Controller
{
    public function __invoke(Request $request, CreditPayment $payment, PaymentService $payments)
    {

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user) {
            return back()->with('warning', __('validations/validations.payment_void.admin_only'));
        }

        try {
            $payments->void($user, $payment, (string) $request->input('void_reason', ''));
        } catch (PaymentException $e) {
            $response = back()->with('warning', $e->getMessage());

            return $e->errorCode === 'void_reason_required' ? $response->withInput() : $response;
        }

        return back()->with('success', __('validations/validations.payment_void.void_success'));
    }
}
