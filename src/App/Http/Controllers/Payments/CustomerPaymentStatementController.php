<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;

class CustomerPaymentStatementController extends Controller
{
    public function show(\Illuminate\Http\Request $request, Credit $credit)
    {
        $lang = (string) $request->get('lang', app()->getLocale());

        if (! in_array($lang, ['tk', 'ru', 'en', 'tr'], true)) {
            $lang = app()->getLocale();
        }

        app()->setLocale($lang);

        $credit->load([
            'paidUpdatedByUser:id,firstname,lastname',
        ]);

        $payments = CreditPayment::query()
            ->with([
                'createdByUser:id,firstname,lastname',
                'correctedByUser:id,firstname,lastname',
                'voidedByUser:id,firstname,lastname',
            ])
            ->where('credit_logicalref', (int) $credit->logicalref)
            ->orderByDesc('id')
            ->get();

        return view('pages.payments.statement', [
            'credit' => $credit,
            'payments' => $payments,
            'lang' => $lang,
        ]);
    }
}
