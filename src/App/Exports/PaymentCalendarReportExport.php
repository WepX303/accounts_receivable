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
            'Expected Amount',
            'Paid Expected Amount',
            'Total Received Amount',
            'Change Returned',
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
            ->get([
                'source_id',
                'date_',
                'amount_local',
                'amount',
            ]);

        $expectedRows = collect();
        $expectedCreditIdsByDate = collect();

        foreach ($credits as $credit) {
            $amount = (float) ($credit->amount_local ?? $credit->amount ?? 0);
            $monthlyPayment = round($amount / 6, 2);

            if ($amount <= 0 || $monthlyPayment <= 0) {
                continue;
            }

            $creditStartDate = Carbon::parse($credit->date_)->startOfDay();

            for ($i = 1; $i <= 6; $i++) {
                $dueDate = $creditStartDate->copy()->addMonthsNoOverflow($i)->startOfDay();

                if (! $dueDate->betweenIncluded($start, $end)) {
                    continue;
                }

                $dateKey = $dueDate->toDateString();

                $expectedRows[$dateKey] = (float) ($expectedRows[$dateKey] ?? 0) + $monthlyPayment;

                if (! isset($expectedCreditIdsByDate[$dateKey])) {
                    $expectedCreditIdsByDate[$dateKey] = collect();
                }

                $expectedCreditIdsByDate[$dateKey]->push((int) $credit->source_id);
            }
        }

        $totalReceivedRows = CreditPayment::query()
            ->notVoided()
            ->selectRaw('DATE(created_at) as pay_date')
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as received_amount')
            ->whereBetween('created_at', [
                $start->copy()->startOfDay(),
                $end->copy()->endOfDay(),
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('received_amount', 'pay_date');

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

        $paidExpectedRows = collect();

        foreach ($expectedCreditIdsByDate as $dateKey => $creditIds) {
            $ids = $creditIds->filter()->unique()->values();

            if ($ids->isEmpty()) {
                $paidExpectedRows[$dateKey] = 0;
                continue;
            }

            $paidExpectedRows[$dateKey] = (float) CreditPayment::query()
                ->notVoided()
                ->whereIn('credit_source_id', $ids)
                ->whereBetween('created_at', [
                    Carbon::parse($dateKey)->startOfDay(),
                    Carbon::parse($dateKey)->endOfDay(),
                ])
                ->sum(DB::raw('pay_amount - COALESCE(change_amount, 0)'));
        }

        $rows = [];
        $cursor = $start->copy();

        while ($cursor <= $end) {
            $dateKey = $cursor->toDateString();

            $expected = round((float) ($expectedRows[$dateKey] ?? 0), 2);
            $paidExpected = round((float) ($paidExpectedRows[$dateKey] ?? 0), 2);
            $totalReceived = round((float) ($totalReceivedRows[$dateKey] ?? 0), 2);
            $change = round((float) ($changeRows[$dateKey] ?? 0), 2);
            $difference = round($paidExpected - $expected, 2);

            $percent = $expected > 0
                ? round(($paidExpected / $expected) * 100, 2)
                : 0;

            $rows[] = [
                $cursor->format('d.m.Y'),
                $cursor->format('l'),
                number_format($expected, 2) . ' TMT',
                number_format($paidExpected, 2) . ' TMT',
                number_format($totalReceived, 2) . ' TMT',
                number_format($change, 2) . ' TMT',
                number_format($difference, 2) . ' TMT',
                number_format($percent, 2) . '%',
                $this->statusText($expected, $paidExpected, $percent, $cursor),
            ];

            $cursor->addDay();
        }

        $expectedTotal = $this->sumMoneyColumn($rows, 2);
        $paidExpectedTotal = $this->sumMoneyColumn($rows, 3);
        $totalReceivedTotal = $this->sumMoneyColumn($rows, 4);
        $changeTotal = $this->sumMoneyColumn($rows, 5);
        $differenceTotal = round($paidExpectedTotal - $expectedTotal, 2);

        $percentTotal = $expectedTotal > 0
            ? round(($paidExpectedTotal / $expectedTotal) * 100, 2)
            : 0;

        $rows[] = [
            'Total',
            '',
            number_format($expectedTotal, 2) . ' TMT',
            number_format($paidExpectedTotal, 2) . ' TMT',
            number_format($totalReceivedTotal, 2) . ' TMT',
            number_format($changeTotal, 2) . ' TMT',
            number_format($differenceTotal, 2) . ' TMT',
            number_format($percentTotal, 2) . '%',
            '',
        ];

        return $rows;
    }

    private function statusText(float $expected, float $paidExpected, float $percent, Carbon $date): string
    {
        if ($expected <= 0) {
            return 'No Expected Payment';
        }

        if ($percent >= 100) {
            return 'Completed';
        }

        if ($paidExpected > 0) {
            return 'Partially Paid';
        }

        if ($date->isToday()) {
            return 'Due Today';
        }

        if ($date->isPast()) {
            return 'Missing';
        }

        return 'Upcoming';
    }

    private function sumMoneyColumn(array $rows, int $index): float
    {
        return round((float) collect($rows)->sum(function ($row) use ($index) {
            return (float) str_replace([',', ' TMT'], '', $row[$index] ?? 0);
        }), 2);
    }
}