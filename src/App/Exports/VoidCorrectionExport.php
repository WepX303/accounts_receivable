<?php

namespace App\Exports;

use App\Services\Reports\VoidCorrectionReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VoidCorrectionExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private Request $request) {}

    public function headings(): array
    {
        return [
            __('pages/reports.void_correction.event_at'),
            __('pages/reports.common.type'),
            __('pages/reports.export.payment_id'),
            __('pages/reports.void_correction.related_payment_id'),
            __('pages/reports.export.payment_date'),
            __('pages/reports.common.customer'),
            __('pages/reports.common.contract'),
            __('pages/reports.common.phone'),
            __('pages/reports.common.branch'),
            __('pages/reports.common.cashier'),
            __('pages/reports.void_correction.old_amount'),
            __('pages/reports.void_correction.new_amount'),
            __('pages/reports.void_correction.delta'),
            __('pages/reports.void_correction.actor'),
            __('pages/reports.void_correction.reason'),
        ];
    }

    public function collection(): Collection
    {
        $service = new VoidCorrectionReportService($this->request);

        $rows = $service->rows()->map(function (array $r) {
            return [
                $r['event_at'] ? Carbon::parse($r['event_at'])->format('Y-m-d H:i:s') : '',
                $r['type'] === VoidCorrectionReportService::TYPE_VOID
                    ? __('pages/reports.void_correction.type_void')
                    : __('pages/reports.void_correction.type_correction'),
                $r['payment_id'],
                $r['related_payment_id'] ?? '',
                $r['payment_at'] ? Carbon::parse($r['payment_at'])->format('Y-m-d H:i:s') : '',
                (string) $r['customer_name'],
                (string) $r['customer_contract'],
                (string) $r['customer_phone'],
                (string) $r['branch'],
                (string) $r['cashier'],
                round((float) $r['old_amount'], 2),
                round((float) $r['new_amount'], 2),
                round((float) $r['delta'], 2),
                (string) $r['actor_name'],
                (string) $r['reason'],
            ];
        });

        $rows->push([
            __('pages/reports.export.total_upper'),
            '', '', '', '', '', '', '', '', '',
            round((float) $rows->sum(fn ($r) => (float) ($r[10] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[11] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[12] ?? 0)), 2),
            '',
            '',
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
