<?php

namespace App\Exports;

use App\Models\Credit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomersExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading
{
    public function __construct(private Request $request) {}

    public function query()
    {
        $q = trim((string) ($this->request->get('q') ?? ''));
        if ($q === 'null') $q = '';

        $quick = (string) $this->request->get('quick_filter', 'all');

        [$from, $to] = match ($quick) {
            'paid_today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'paid_yesterday' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            'paid_7d' => [Carbon::now()->subDays(7), Carbon::now()],
            'paid_14d' => [Carbon::now()->subDays(14), Carbon::now()],
            'paid_1m' => [Carbon::now()->subMonth(), Carbon::now()],
            'paid_3m' => [Carbon::now()->subMonths(3), Carbon::now()],
            'paid_6m' => [Carbon::now()->subMonths(6), Carbon::now()],
            'paid_9m' => [Carbon::now()->subMonths(9), Carbon::now()],
            'paid_12m' => [Carbon::now()->subMonths(12), Carbon::now()],
            default => [null, null],
        };

        $query = Credit::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($qq) use ($like) {
                    $qq->where('name', 'ilike', $like)
                        ->orWhere('phone', 'ilike', $like)
                        ->orWhere('passport', 'ilike', $like)
                        ->orWhere('contract', 'ilike', $like)
                        ->orWhere('clientref', 'ilike', $like)
                        ->orWhere('assurance', 'ilike', $like);
                });
            })
            ->when($from && $to, function ($query) use ($from, $to) {
                $table = $query->getModel()->getTable();

                $query->whereExists(function ($sub) use ($from, $to, $table) {
                    $sub->selectRaw('1')
                        ->from('credit_payments')
                        ->whereColumn('credit_payments.credit_logicalref', $table.'.logicalref')
                        ->whereBetween('credit_payments.created_at', [$from, $to]);
                });
            })
            ->when($quick === 'has_debt', fn ($q) => $q->whereRaw('COALESCE(amount, 0) > COALESCE(paid, 0)'))
            ->when($quick === 'no_debt', fn ($q) => $q->whereRaw('COALESCE(amount, 0) <= COALESCE(paid, 0)'))
            ->when($quick === 'no_payment', function ($query) {
                $table = $query->getModel()->getTable();
                $query->whereNotExists(function ($sub) use ($table) {
                    $sub->selectRaw('1')
                        ->from('credit_payments')
                        ->whereColumn('credit_payments.credit_logicalref', $table.'.logicalref');
                });
            })
            ->when($quick === 'blocked', fn ($q) => $q->where('active', false))
            ->when($quick === 'active', fn ($q) => $q->where('active', true))
            ->when($quick === 'bermejek', function ($q) {
                $q->whereNotNull('status')
                    ->where('status', '!=', '')
                    ->whereRaw('LOWER(TRIM(status)) = ?', ['bermejek']);
            })
            ->when($quick === 'paid_mismatch', function ($query) {
                $query->whereNotNull('paid_local')
                    ->whereRaw('ROUND(COALESCE(paid_local,0)::numeric, 2) <> ROUND(COALESCE(paid,0)::numeric, 2)');
            })
            ->orderByDesc('rv_bigint');

        return $query;
    }

    public function chunkSize(): int
    {
        return 50; // istersen 1000 de yapabiliriz
    }

    public function headings(): array
    {
        return [
            'LogicalRef',
            'Date',
            'Name',
            'Phone',
            'Passport',
            'ClientRef',
            'Branch',
            'Contract',
            'Amount Local',
            'Amount Remote',
            'Paid Local',
            'Paid Remote',
            'Local Remaining',
            'Remote Remaining',
            'Status',
            'Active',
            'Note',
        ];
    }

    public function map($c): array
    {
        return [
            $c->logicalref,
            $c->date_ ? Carbon::parse($c->date_)->format('Y-m-d') : null,
            $c->name,
            $c->phone,
            $c->passport,
            $c->clientref,
            $c->branch,
            $c->contract,
            round((float) ($c->amount_local ?? $c->amount ?? 0), 2),
            round((float) ($c->amount ?? 0), 2),
            round((float) ($c->paid_local ?? $c->paid ?? 0), 2),
            round((float) ($c->paid ?? 0), 2),
            $c->local_remaining,
            $c->remote_remaining,
            $c->status,
            $c->active ? 'YES' : 'NO',
            $c->note,
        ];
    }
}