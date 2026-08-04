<?php

namespace App\Exports;

use App\Services\Reports\BalanceIntegrityReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BalanceIntegrityExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private Request $request) {}

    public function headings(): array
    {
        return [
            __('pages/reports.export.credit_source_id'),
            __('pages/reports.export.logicalref'),
            __('pages/reports.common.customer'),
            __('pages/reports.common.contract'),
            __('pages/reports.common.phone'),
            __('pages/reports.common.branch'),
            __('pages/reports.export.local_amount'),
            __('pages/reports.balance_integrity.baseline_paid'),
            __('pages/reports.balance_integrity.applied_sum'),
            __('pages/reports.balance_integrity.expected_paid'),
            __('pages/reports.balance_integrity.actual_paid'),
            __('pages/reports.balance_integrity.diff'),
            __('pages/reports.export.payment_count'),
            __('pages/reports.balance_integrity.voided_payment_count'),
            __('pages/reports.export.paid_updated_at'),
            __('pages/reports.export.paid_note'),
        ];
    }

    public function collection(): Collection
    {
        $service = new BalanceIntegrityReportService($this->request);

        $rows = $service->rows()->map(function (array $r) {
            return [
                $r['source_id'],
                $r['logicalref'],
                (string) $r['name'],
                (string) $r['contract'],
                (string) $r['phone'],
                (string) $r['branch'],
                $r['amount_local'],
                $r['baseline_paid'],
                $r['applied_sum'],
                $r['expected_paid'],
                $r['actual_paid'],
                $r['diff'],
                $r['active_payment_count'],
                $r['voided_payment_count'],
                $r['paid_updated_at'] ? Carbon::parse($r['paid_updated_at'])->format('Y-m-d H:i:s') : '',
                (string) $r['paid_note'],
            ];
        });

        $rows->push([
            __('pages/reports.export.total_upper'),
            '', '', '', '', '', '', '', '', '', '',
            round((float) $rows->sum(fn ($r) => (float) ($r[11] ?? 0)), 2),
            '', '', '', '',
        ]);

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->getStyle($highestRow . ':' . $highestRow)->getFont()->setBold(true);

        return [];
    }
}
