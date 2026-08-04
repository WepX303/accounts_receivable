<?php

namespace App\Exports;

use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CollectionPerformanceExport implements WithMultipleSheets
{
    public function __construct(private Request $request) {}

    public function sheets(): array
    {
        return [
            new CollectionPerformanceSheet($this->request, 'cashier'),
            new CollectionPerformanceSheet($this->request, 'branch'),
        ];
    }
}

class CollectionPerformanceSheet implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        private Request $request,
        private string $type
    ) {}

    public function title(): string
    {
        return $this->type === 'cashier'
            ? __('pages/reports.export.sheet_cashiers')
            : __('pages/reports.export.sheet_branches');
    }

    public function headings(): array
    {
        return [
            $this->type === 'cashier'
                ? __('pages/reports.common.cashier')
                : __('pages/reports.common.branch'),
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

        if ($this->type === 'cashier') {
            $rows = (clone $base)
                ->selectRaw("COALESCE(created_by_name, 'N/A') as name")
                ->selectRaw('COUNT(*) as tx_count')
                ->selectRaw('COALESCE(SUM(pay_amount), 0) as gross_total')
                ->selectRaw('COALESCE(SUM(change_amount), 0) as change_total')
                ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as net_total')
                ->selectRaw('COALESCE(SUM(cash_amount), 0) as cash_total')
                ->selectRaw('COALESCE(SUM(card_amount), 0) as card_total')
                ->selectRaw('COALESCE(SUM(phone_amount), 0) as phone_total')
                ->groupBy('name')
                ->orderByDesc('net_total')
                ->get();
        } else {
            $rows = (clone $base)
                ->selectRaw("COALESCE(branch, 'N/A') as name")
                ->selectRaw('COUNT(*) as tx_count')
                ->selectRaw('COALESCE(SUM(pay_amount), 0) as gross_total')
                ->selectRaw('COALESCE(SUM(change_amount), 0) as change_total')
                ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as net_total')
                ->selectRaw('COALESCE(SUM(cash_amount), 0) as cash_total')
                ->selectRaw('COALESCE(SUM(card_amount), 0) as card_total')
                ->selectRaw('COALESCE(SUM(phone_amount), 0) as phone_total')
                ->groupBy('name')
                ->orderByDesc('net_total')
                ->get();
        }

        $exportRows = $rows->map(function ($r) {
            return [
                $r->name,
                (int) $r->tx_count,
                round((float) $r->gross_total, 2),
                round((float) $r->change_total, 2),
                round((float) $r->net_total, 2),
                round((float) $r->cash_total, 2),
                round((float) $r->card_total, 2),
                round((float) $r->phone_total, 2),
            ];
        });

        $exportRows->push([
            __('pages/reports.export.total_upper'),
            (int) $exportRows->sum(fn ($r) => (int) ($r[1] ?? 0)),
            round((float) $exportRows->sum(fn ($r) => (float) ($r[2] ?? 0)), 2),
            round((float) $exportRows->sum(fn ($r) => (float) ($r[3] ?? 0)), 2),
            round((float) $exportRows->sum(fn ($r) => (float) ($r[4] ?? 0)), 2),
            round((float) $exportRows->sum(fn ($r) => (float) ($r[5] ?? 0)), 2),
            round((float) $exportRows->sum(fn ($r) => (float) ($r[6] ?? 0)), 2),
            round((float) $exportRows->sum(fn ($r) => (float) ($r[7] ?? 0)), 2),
        ]);

        return $exportRows;
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
        $method = (string) $this->request->get('method', 'all');

        return CreditPayment::query()
            ->notVoided()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($branch !== '', fn ($q) => $q->where('branch', $branch))
            ->when($cashier !== '', fn ($q) => $q->where('created_by_name', $cashier))
            ->when($method === 'cash', fn ($q) => $q->where('cash_amount', '>', 0))
            ->when($method === 'card', fn ($q) => $q->where('card_amount', '>', 0))
            ->when($method === 'phone', fn ($q) => $q->where('phone_amount', '>', 0));
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->getStyle($highestRow . ':' . $highestRow)->getFont()->setBold(true);

        return [];
    }
}