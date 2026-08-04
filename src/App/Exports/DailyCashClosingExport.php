<?php

namespace App\Exports;

use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailyCashClosingExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private Request $request) {}

    public function headings(): array
    {
        return [
            __('pages/reports.common.date'),
            __('pages/reports.common.transactions'),
            __('pages/reports.common.gross_collection'),
            __('pages/reports.common.change_returned'),
            __('pages/reports.common.net_collection'),
            __('pages/reports.export.cash_amount'),
            __('pages/reports.export.card_amount'),
            __('pages/reports.export.phone_amount'),
        ];
    }

    public function collection(): Collection
    {
        $base = $this->baseQuery();

        $rows = (clone $base)
            ->selectRaw('DATE(created_at) as report_date')
            ->selectRaw('COUNT(*) as tx_count')
            ->selectRaw('COALESCE(SUM(pay_amount), 0) as gross_total')
            ->selectRaw('COALESCE(SUM(change_amount), 0) as change_total')
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as net_total')
            ->selectRaw('COALESCE(SUM(cash_amount), 0) as cash_total')
            ->selectRaw('COALESCE(SUM(card_amount), 0) as card_total')
            ->selectRaw('COALESCE(SUM(phone_amount), 0) as phone_total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderByDesc('report_date')
            ->get()
            ->map(function ($r) {
                return [
                    Carbon::parse($r->report_date)->format('Y-m-d'),
                    (int) $r->tx_count,
                    round((float) $r->gross_total, 2),
                    round((float) $r->change_total, 2),
                    round((float) $r->net_total, 2),
                    round((float) $r->cash_total, 2),
                    round((float) $r->card_total, 2),
                    round((float) $r->phone_total, 2),
                ];
            });

        $rows->push([
            __('pages/reports.export.total_upper'),
            (int) $rows->sum(fn ($r) => (int) ($r[1] ?? 0)),
            round((float) $rows->sum(fn ($r) => (float) ($r[2] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[3] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[4] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[5] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[6] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[7] ?? 0)), 2),
        ]);

        return $rows;
    }

    private function baseQuery()
    {
        $dateFrom = $this->request->filled('date_from')
            ? Carbon::parse($this->request->date_from)->startOfDay()
            : now()->startOfDay();

        $dateTo = $this->request->filled('date_to')
            ? Carbon::parse($this->request->date_to)->endOfDay()
            : now()->endOfDay();

        $branch = trim((string) $this->request->get('branch', ''));
        $cashier = trim((string) $this->request->get('cashier', ''));

        return CreditPayment::query()
            ->notVoided()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($branch !== '', fn ($q) => $q->where('branch', $branch))
            ->when($cashier !== '', fn ($q) => $q->where('created_by_name', $cashier));
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->getStyle($highestRow . ':' . $highestRow)->getFont()->setBold(true);

        return [];
    }
}