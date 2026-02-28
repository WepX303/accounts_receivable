<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\UserRoleEnum;


class PaymentVoidController extends Controller
{
    public function __invoke(Request $request, CreditPayment $payment)
    {
        // Admin only
        $user = Auth::user();

        if (!$user || $user->role !== UserRoleEnum::ADMIN) {
            return back()->with('warning', __('validations/validations.payment_void.admin_only'));
        }

        $reason = trim((string) $request->input('void_reason', ''));
        $reason = preg_replace('/\s+/', ' ', $reason);

        if ($reason === '') {
            return back()->with('warning', __('validations/validations.payment_void.void_reason_required'))->withInput();
        }

        try {
            DB::transaction(function () use ($payment, $reason, $user) {

                /** @var CreditPayment $p */
                $p = CreditPayment::query()
                    ->whereKey($payment->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($p->voided_at !== null) {
                    throw new \RuntimeException(__('validations/validations.payment_void.already_voided'));
                }


                /** @var Credit $c */
                $c = Credit::query()
                    ->where('logicalref', (int) $p->credit_logicalref)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($c->amount_local === null || $c->paid_local === null) {
                    throw new \RuntimeException(__('validations/validations.payment_void.local_debt_missing'));
                }

                $received = (float) $p->pay_amount;
                $change = (float) ($p->change_amount ?? 0);
                $applied = $received - $change;
                if ($applied < 0) $applied = 0;
                $applied = round($applied, 2);

                $paidLocal = (float) $c->paid_local;
                $newPaidLocal = $paidLocal - $applied;
                if ($newPaidLocal < 0) $newPaidLocal = 0;


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

                // Payment void flag
                $p->forceFill([
                    'voided_at' => now(),
                    'voided_by' => (int) $user->id,
                    'void_reason' => $reason,
                ])->save();
            });
        } catch (\Throwable $e) {
            $msg = $e instanceof \RuntimeException ? $e->getMessage() : __('validations/validations.payment_void.void_failed');
            return back()->with('warning', $msg);
        }

        return back()->with('success', __('validations/validations.payment_void.void_success'));
    }
}
