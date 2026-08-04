<?php

namespace App\Exports;

use App\Services\Reports\PaymentCalendarReportService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentCalendarReportExport implements FromArray, WithHeadings
{
    private PaymentCalendarReportService $service;

    /**
     * @param  string[]  $branches  Empty means all branches.
     */
    public function __construct(string $month, array $branches = [])
    {
        $this->service = new PaymentCalendarReportService($month, $branches);
    }

    public function headings(): array
    {
        return [
            __('pages/reports.common.date'),
            __('pages/reports.common.day'),
            __('pages/reports.export.expected_amount'),
            __('pages/reports.export.paid_expected_amount'),
            __('pages/reports.export.total_received_amount'),
            __('pages/reports.common.change_returned'),
            __('pages/reports.payment_calendar.difference'),
            __('pages/reports.payment_calendar.collection_percent'),
            __('pages/reports.common.status'),
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->service->days() as $day) {
            $rows[] = [
                $day['date']->format('d.m.Y'),
                $day['day_name'],
                number_format($day['expected'], 2) . ' TMT',
                number_format($day['paid_expected'], 2) . ' TMT',
                number_format($day['total_received'], 2) . ' TMT',
                number_format($day['change'], 2) . ' TMT',
                number_format($day['difference'], 2) . ' TMT',
                number_format($day['percent'], 2) . '%',
                $this->statusText($day['expected'], $day['paid_expected'], $day['percent'], $day['date']),
            ];
        }

        // Totals come from the service rather than from parsing the formatted
        // strings back out of the rows above.
        $summary = $this->service->summary();

        $rows[] = [
            __('pages/reports.common.total'),
            '',
            number_format($summary['expected_total'], 2) . ' TMT',
            number_format($summary['paid_expected_total'], 2) . ' TMT',
            number_format($summary['total_received_total'], 2) . ' TMT',
            number_format($summary['change_total'], 2) . ' TMT',
            number_format($summary['difference_total'], 2) . ' TMT',
            number_format($summary['percent_total'], 2) . '%',
            '',
        ];

        return $rows;
    }

    private function statusText(float $expected, float $paidExpected, float $percent, Carbon $date): string
    {
        if ($expected <= 0) {
            return __('pages/reports.payment_calendar.status.no_expected');
        }

        if ($percent >= 100) {
            return __('pages/reports.payment_calendar.status.completed');
        }

        if ($paidExpected > 0) {
            return __('pages/reports.payment_calendar.status.partially_paid');
        }

        if ($date->isToday()) {
            return __('pages/reports.payment_calendar.status.due_today');
        }

        if ($date->isPast()) {
            return __('pages/reports.payment_calendar.status.missing');
        }

        return __('pages/reports.payment_calendar.status.upcoming');
    }
}
