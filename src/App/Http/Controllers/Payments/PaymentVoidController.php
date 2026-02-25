<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentVoidController extends Controller
{
    public function __invoke(Request $request, CreditPayment $payment)
    {
        // Admin-only (route middleware de koyacağız ama burada da garanti)
        $user = Auth::user();
        if (! $user || (string)$user->role !== 'Admin') {
            return back()->with('warning', 'Only Admin can void payments.');
        }

        $reason = trim((string) $request->input('void_reason', ''));
        $reason = preg_replace('/\s+/', ' ', $reason);

        if ($reason === '') {
            return back()->with('warning', 'Void reason is required.')->withInput();
        }

        try {
            DB::transaction(function () use ($payment, $reason, $user) {

                /** @var CreditPayment $p */
                $p = CreditPayment::query()
                    ->whereKey($payment->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($p->voided_at !== null) {
                    throw new \RuntimeException('This payment is already voided.');
                }

                /** @var Credit $c */
                $c = Credit::query()
                    ->where('logicalref', (int) $p->credit_logicalref)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($c->amount_local === null || $c->paid_local === null) {
                    throw new \RuntimeException('Local debt data missing (amount_local/paid_local NULL).');
                }

                // ödeme borca ne kadar uygulanmıştı?
                $received = (float) $p->pay_amount;
                $change = (float) ($p->change_amount ?? 0);
                $applied = $received - $change;
                if ($applied < 0) $applied = 0;
                $applied = round($applied, 2);

                $paidLocal = (float) $c->paid_local;
                $newPaidLocal = $paidLocal - $applied;
                if ($newPaidLocal < 0) $newPaidLocal = 0;

                // credit güncelle (paid_local geri alınır)
                $c->forceFill([
                    'paid_local' => number_format(round($newPaidLocal, 2), 2, '.', ''),
                    'paid_updated_by' => (int) $user->id,
                    'paid_updated_at' => now(), // void işlemi anı (payment_at değil)
                    'paid_note' => 'voided=yes'
                        .' | voided_payment_id='.$p->id
                        .' | voided_applied='.number_format($applied, 2, '.', '')
                        .' | void_reason='.$reason
                        .' | voided_by='.$user->full_name
                        .' | voided_at='.now()->format('Y-m-d H:i:s'),
                ])->save();

                // payment void işaretle
                $p->forceFill([
                    'voided_at' => now(),
                    'voided_by' => (int) $user->id,
                    'void_reason' => $reason,
                ])->save();
            });

        } catch (\Throwable $e) {
            $msg = $e instanceof \RuntimeException ? $e->getMessage() : 'Payment void failed.';
            return back()->with('warning', $msg);
        }

        return back()->with('success', 'Payment voided successfully.');
    }
}