<?php

namespace App\Exports;

use App\Models\Credit;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentCalendarReportExport implements FromArray, WithHeadings
{
    public function __construct(
        private string $month
    ) {}

    public function headings(): array
    {
        return [
            'Date',
            'Day',
            'Expected',
            'Received',
            'Change',
            'Difference',
            'Collection %',
            'Status',
        ];
    }

    public function array(): array
    {
        try {
            $currentMonth = Carbon::parse($this->month . '-01')->startOfMonth();
        } catch (\Throwable $e) {
            $currentMonth = now()->startOfMonth();
        }

        $start = $currentMonth->copy()->startOfMonth();
        $end = $currentMonth->copy()->endOfMonth();

        $credits = Credit::query()
            ->where('active', true)
            ->where('is_blocked', 0)
            ->whereNotNull('date_')
            ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)')
            ->get(['date_', 'amount_local', 'amount']);

        $expectedRows = collect();

        foreach ($credits as $credit) {
            $amount = (float) ($credit->amount_local ?? $credit->amount ?? 0);
            $monthlyPayment = round($amount / 6, 2);

            if ($amount <= 0 || $monthlyPayment <= 0) {
                continue;
            }

            $creditStartDate = Carbon::parse($credit->date_)->startOfDay();

            for ($i = 1; $i <= 6; $i++) {
                $dueDate = $creditStartDate->copy()->addMonthsNoOverflow($i)->startOfDay();

                if ($dueDate->betweenIncluded($start, $end)) {
                    $key = $dueDate->toDateString();
                    $expectedRows[$key] = (float) ($expectedRows[$key] ?? 0) + $monthlyPayment;
                }
            }
        }

        $receivedRows = CreditPayment::query()
            ->notVoided()
            ->selectRaw('DATE(created_at) as pay_date')
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as received_amount')
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('received_amount', 'pay_date');

        $changeRows = CreditPayment::query()
            ->notVoided()
            ->selectRaw('DATE(created_at) as pay_date')
            ->selectRaw('COALESCE(SUM(change_amount), 0) as change_amount')
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('change_amount', 'pay_date');

        $rows = [];
        $cursor = $start->copy();

        while ($cursor <= $end) {
            $dateKey = $cursor->toDateString();

            $expected = round((float) ($expectedRows[$dateKey] ?? 0), 2);
            $received = round((float) ($receivedRows[$dateKey] ?? 0), 2);
            $change = round((float) ($changeRows[$dateKey] ?? 0), 2);
            $difference = round($received - $expected, 2);

            $percent = $expected > 0
                ? round(($received / $expected) * 100, 2)
                : 0;

            $status = 'No Expected Payment';

            if ($expected > 0 && $percent >= 100) {
                $status = 'Completed';
            } elseif ($expected > 0 && $received > 0) {
                $status = 'Partially Paid';
            } elseif ($expected > 0 && $received <= 0 && $cursor->isPast() && ! $cursor->isToday()) {
                $status = 'Missing';
            } elseif ($expected > 0 && $cursor->isToday()) {
                $status = 'Due Today';
            } elseif ($expected > 0 && $cursor->isFuture()) {
                $status = 'Upcoming';
            }

            $rows[] = [
                $cursor->format('d.m.Y'),
                $cursor->format('l'),
                number_format($expected, 2) . ' TMT',
                number_format($received, 2) . ' TMT',
                number_format($change, 2) . ' TMT',
                number_format($difference, 2) . ' TMT',
                number_format($percent, 2) . '%',
                $status,
            ];

            $cursor->addDay();
        }

        $expectedTotal = round((float) collect($rows)->sum(function ($row) {
            return (float) str_replace([',', ' TMT'], '', $row[2]);
        }), 2);

        $receivedTotal = round((float) collect($rows)->sum(function ($row) {
            return (float) str_replace([',', ' TMT'], '', $row[3]);
        }), 2);

        $changeTotal = round((float) collect($rows)->sum(function ($row) {
            return (float) str_replace([',', ' TMT'], '', $row[4]);
        }), 2);

        $differenceTotal = round($receivedTotal - $expectedTotal, 2);

        $percentTotal = $expectedTotal > 0
            ? round(($receivedTotal / $expectedTotal) * 100, 2)
            : 0;

        $rows[] = [
            'Total',
            '',
            number_format($expectedTotal, 2) . ' TMT',
            number_format($receivedTotal, 2) . ' TMT',
            number_format($changeTotal, 2) . ' TMT',
            number_format($differenceTotal, 2) . ' TMT',
            number_format($percentTotal, 2) . '%',
            '',
        ];

        return $rows;
    }
}
