<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaymentCalendarReportDetailController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'type' => ['required', 'in:expected,received'],
        ]);

        $date = Carbon::parse($data['date'])->startOfDay();
        $type = $data['type'];

        if ($type === 'received') {
            $rows = CreditPayment::query()
                ->notVoided()
                ->whereBetween('created_at', [
                    $date->copy()->startOfDay(),
                    $date->copy()->endOfDay(),
                ])
                ->orderByDesc('id')
                ->paginate(50)
                ->appends($request->query());
                

            return view('pages.reports.payment-calendar.details', compact('rows', 'date', 'type'));
        }

        $rows = Credit::query()
            ->where('active', true)
            ->where('is_blocked', 0)
            ->whereNotNull('date_')
            ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)')
            ->whereRaw("
                EXISTS (
                    SELECT 1
                    FROM generate_series(1, 6) AS installment_no
                    WHERE (
                        date_::date + (installment_no * INTERVAL '1 month')
                    )::date = ?::date
                )
            ", [$date->toDateString()])
            ->orderBy('branch')
            ->orderBy('name')
            ->paginate(50)
            ->appends($request->query());

            

        return view('pages.reports.payment-calendar.details', compact('rows', 'date', 'type'));
    }
}