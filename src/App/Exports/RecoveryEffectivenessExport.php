<?php

namespace App\Exports;

use App\Models\Credit;
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

class RecoveryEffectivenessExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private Request $request) {}

    public function headings(): array
    {
        return [
            'Credit ID',
            'LogicalRef',
            'Customer',
            'Contract',
            'Phone',
            'Passport',
            'Branch',
            'ClientRef',
            'Credit Date',
            'Credit Status',

            'Total Debt',
            'Paid',
            'Remaining',

            'Payment Count',
            'Gross Recovered',
            'Change Returned',
            'Net Recovered',
            'Recovery %',
            'Last Payment',

            'Remote Amount',
            'Remote Paid',
            'Local Amount',
            'Local Paid',

            'Manager',
            'Assurance',
            'Customer Status',
            'Card No',
            'Fish No',
            'Confirmed By',
            'G Status',
            'Note',
            'Created At',
            'Updated At',
        ];
    }

    public function collection(): Collection
    {
        $dateFrom = $this->request->filled('date_from')
            ? Carbon::parse($this->request->date_from)->startOfDay()
            : now()->startOfMonth();

        $dateTo = $this->request->filled('date_to')
            ? Carbon::parse($this->request->date_to)->endOfDay()
            : now()->endOfDay();

        $branch = trim((string) $this->request->get('branch', ''));
        $q = trim((string) $this->request->get('q', ''));
        $minRecovered = $this->request->filled('min_recovered')
            ? (float) $this->request->get('min_recovered')
            : null;

        $sort = (string) $this->request->get('sort', 'recovered_desc');

        $creditQuery = Credit::query()
            ->where('active', true)
            ->where('is_blocked', 0)
            ->whereRaw('COALESCE(amount_local, amount, 0) > 0')
            ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)');

        if ($branch !== '') {
            $creditQuery->where('branch', $branch);
        }

        if ($q !== '') {
            $like = '%' . $q . '%';

            $creditQuery->where(function ($sub) use ($like) {
                $sub->where('name', 'ilike', $like)
                    ->orWhere('phone', 'ilike', $like)
                    ->orWhere('passport', 'ilike', $like)
                    ->orWhere('contract', 'ilike', $like)
                    ->orWhere('clientref', 'ilike', $like);
            });
        }

        $credits = $creditQuery->get([
            'source_id',
            'logicalref',
            'branch',
            'name',
            'phone',
            'passport',
            'contract',
            'clientref',
            'date_',
            'amount',
            'amount_local',
            'paid',
            'paid_local',
            'status',
            'manager',
            'assurance',
            'custstatus',
            'cardno',
            'fishno',
            'confirmedby',
            'gstatus',
            'note',
            'created_at',
            'updated_at',
        ]);

        $creditIds = $credits->pluck('source_id')
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->values();

        $paymentsByCredit = CreditPayment::query()
            ->notVoided()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereIn('credit_source_id', $creditIds)
            ->selectRaw('credit_source_id')
            ->selectRaw('COUNT(*) as payment_count')
            ->selectRaw('COALESCE(SUM(pay_amount), 0) as gross_recovered')
            ->selectRaw('COALESCE(SUM(change_amount), 0) as change_total')
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as net_recovered')
            ->selectRaw('MAX(created_at) as last_payment_at')
            ->groupBy('credit_source_id')
            ->get()
            ->keyBy('credit_source_id');

        $rows = collect();

        foreach ($credits as $credit) {
            $total = (float) ($credit->amount_local ?? $credit->amount ?? 0);
            $paid = (float) ($credit->paid_local ?? $credit->paid ?? 0);
            $remaining = round(max($total - $paid, 0), 2);

            if ($total <= 0 || $remaining <= 0.01) {
                continue;
            }

            $paymentAgg = $paymentsByCredit->get((int) $credit->source_id);

            $paymentCount = (int) ($paymentAgg->payment_count ?? 0);
            $grossRecovered = round((float) ($paymentAgg->gross_recovered ?? 0), 2);
            $changeTotal = round((float) ($paymentAgg->change_total ?? 0), 2);
            $netRecovered = round((float) ($paymentAgg->net_recovered ?? 0), 2);
            $lastPaymentAt = !empty($paymentAgg?->last_payment_at)
                ? Carbon::parse($paymentAgg->last_payment_at)
                : null;

            if ($minRecovered !== null && $netRecovered < $minRecovered) {
                continue;
            }

            $recoveryRate = $total > 0
                ? round(($netRecovered / $total) * 100, 2)
                : 0;

            $rows->push([
                $credit->source_id,
                $credit->logicalref,
                $credit->name,
                $credit->contract,
                $credit->phone,
                $credit->passport,
                $credit->branch,
                $credit->clientref,
                $credit->date_ ? Carbon::parse($credit->date_)->format('Y-m-d') : null,
                $credit->status,

                round($total, 2),
                round($paid, 2),
                $remaining,

                $paymentCount,
                $grossRecovered,
                $changeTotal,
                $netRecovered,
                $recoveryRate,
                $lastPaymentAt ? $lastPaymentAt->format('Y-m-d H:i') : null,

                $credit->amount !== null ? round((float) $credit->amount, 2) : null,
                $credit->paid !== null ? round((float) $credit->paid, 2) : null,
                $credit->amount_local !== null ? round((float) $credit->amount_local, 2) : null,
                $credit->paid_local !== null ? round((float) $credit->paid_local, 2) : null,

                $credit->manager,
                $credit->assurance,
                $credit->custstatus,
                $credit->cardno,
                $credit->fishno,
                $credit->confirmedby,
                $credit->gstatus,
                $credit->note,
                $credit->created_at ? Carbon::parse($credit->created_at)->format('Y-m-d H:i') : null,
                $credit->updated_at ? Carbon::parse($credit->updated_at)->format('Y-m-d H:i') : null,
            ]);
        }

        $rows = match ($sort) {
            'recovered_asc' => $rows->sortBy(16),
            'rate_desc' => $rows->sortByDesc(17),
            'rate_asc' => $rows->sortBy(17),
            'remaining_desc' => $rows->sortByDesc(12),
            'customer_asc' => $rows->sortBy(2),
            'branch_asc' => $rows->sortBy(6),
            default => $rows->sortByDesc(16),
        };

        $rows = $rows->values();

        $rows->push([
            '',
            '',
            'TOTAL',
            '',
            '',
            '',
            '',
            '',
            '',
            '',

            round((float) $rows->sum(fn ($r) => (float) ($r[10] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[11] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[12] ?? 0)), 2),

            round((float) $rows->sum(fn ($r) => (float) ($r[13] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[14] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[15] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[16] ?? 0)), 2),
            '',
            '',

            round((float) $rows->sum(fn ($r) => (float) ($r[19] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[20] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[21] ?? 0)), 2),
            round((float) $rows->sum(fn ($r) => (float) ($r[22] ?? 0)), 2),

            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
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