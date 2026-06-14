<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Exports\PaymentCalendarReportExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentCalendarReportController extends Controller
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

        /*
        |--------------------------------------------------------------------------
        | EXPECTED PAYMENTS
        |--------------------------------------------------------------------------
        | Based on credits table, same business idea as SMS controller.
        | Each credit has 6 monthly installments.
        | Daily expected amount = amount_local / 6 for every due installment date.
        */
        $credits = Credit::query()
            ->where('active', true)
            ->where('is_blocked', 0)
            ->whereNotNull('date_')
            ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)')
            ->get([
                'source_id',
                'date_',
                'amount_local',
                'amount',
                'paid_local',
                'paid',
            ]);

        $expectedRows = collect();

        foreach ($credits as $credit) {
            $amount = (float) ($credit->amount_local ?? $credit->amount ?? 0);

            if ($amount <= 0) {
                continue;
            }

            $monthlyPayment = round($amount / 6, 2);

            if ($monthlyPayment <= 0) {
                continue;
            }

            $creditStartDate = Carbon::parse($credit->date_)->startOfDay();

            for ($i = 1; $i <= 6; $i++) {
                $dueDate = $creditStartDate->copy()->addMonthsNoOverflow($i)->startOfDay();

                if ($dueDate->betweenIncluded($start, $end)) {
                    $dateKey = $dueDate->toDateString();

                    $expectedRows[$dateKey] = (float) ($expectedRows[$dateKey] ?? 0) + $monthlyPayment;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | RECEIVED PAYMENTS
        |--------------------------------------------------------------------------
        | Net received = pay_amount - change_amount
        | Voided payments are excluded.
        */
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

        $changeRows = CreditPayment::query()
            ->notVoided()
            ->selectRaw('DATE(created_at) as pay_date')
            ->selectRaw('COALESCE(SUM(change_amount), 0) as change_amount')
            ->whereBetween('created_at', [
                $start->copy()->startOfDay(),
                $end->copy()->endOfDay(),
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('change_amount', 'pay_date');

        while ($cursor <= $end) {
            $dateKey = $cursor->toDateString();

            $expected = round((float) ($expectedRows[$dateKey] ?? 0), 2);
            $received = round((float) ($receivedRows[$dateKey] ?? 0), 2);
            $difference = round($received - $expected, 2);

            $percent = $expected > 0
                ? round(($received / $expected) * 100, 2)
                : 0;

            $change = round((float) ($changeRows[$dateKey] ?? 0), 2);

            $days->push([
                'date' => $cursor->copy(),
                'date_key' => $dateKey,
                'day_name' => $cursor->format('l'),
                'expected' => $expected,
                'received' => $received,
                'difference' => $difference,
                'change' => $change,
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
            'change_total' => round((float) $days->sum('change'), 2),

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
    public function export(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));

        $filename = 'payment_calendar_report_' . $month . '.xlsx';

        return Excel::download(
            new PaymentCalendarReportExport($month),
            $filename
        );
    }
}
