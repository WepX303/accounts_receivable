<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AvshocrecatReport;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentCalendarReportControllercopy extends Controller
{
    public function __invoke(Request $request)
    {
        $month = $request->get('month');

        try {
            $currentMonth = $month
                ? Carbon::parse($month . '-01')->startOfMonth()
                : now()->startOfMonth();
        } catch (\Throwable $e) {
            $currentMonth = now()->startOfMonth();
        }

        $start = $currentMonth->copy()->startOfMonth();
        $end = $currentMonth->copy()->endOfMonth();

        $expectedRows = AvshocrecatReport::query()
            ->selectRaw('tolejek_senesi as pay_date')
            ->selectRaw('COALESCE(SUM(aylyk_tolegi), 0) as expected_amount')
            ->whereNotNull('tolejek_senesi')
            ->whereBetween('tolejek_senesi', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->groupBy('tolejek_senesi')
            ->pluck('expected_amount', 'pay_date');

        $receivedRows = CreditPayment::query()
            ->notVoided()
            ->selectRaw('DATE(created_at) as pay_date')
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as received_amount')
            ->whereBetween('created_at', [
                $start->copy()->startOfDay(),
                $end->copy()->endOfDay(),
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('received_amount', 'pay_date');

        $days = collect();

    

        $cursor = $start->copy();

        while ($cursor <= $end) {
            $dateKey = $cursor->toDateString();

            $expected = (float) ($expectedRows[$dateKey] ?? 0);
            $received = (float) ($receivedRows[$dateKey] ?? 0);
            $difference = $received - $expected;


            $percent = $expected > 0
                ? round(($received / $expected) * 100, 2)
                : 0;

            $days->push([
                'date' => $cursor->copy(),
                'date_key' => $dateKey,
                'day_name' => $cursor->format('l'),
                'expected' => round($expected, 2),
                'received' => round($received, 2),
                'difference' => round($difference, 2),
                'percent' => $percent,
                'is_today' => $cursor->isToday(),
                'is_past' => $cursor->isPast() && ! $cursor->isToday(),
                'is_future' => $cursor->isFuture(),
            ]);

            $cursor->addDay();
        }

        $summary = [
            'expected_total' => round((float) $days->sum('expected'), 2),
            'received_total' => round((float) $days->sum('received'), 2),
            'difference_total' => round((float) $days->sum('difference'), 2),
        ];

        $summary['percent_total'] = $summary['expected_total'] > 0
            ? round(($summary['received_total'] / $summary['expected_total']) * 100, 2)
            : 0;

        return view('pages.reports.payment-calendar.index', [
            'days' => $days,
            'summary' => $summary,
            'currentMonth' => $currentMonth,
            'previousMonth' => $currentMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $currentMonth->copy()->addMonth()->format('Y-m'),
        ]);
    }
}
