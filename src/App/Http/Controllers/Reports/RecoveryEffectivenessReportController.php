<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RecoveryEffectivenessReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'branch' => 'nullable|string|max:100',
            'q' => 'nullable|string|max:100',
            'min_recovered' => 'nullable|numeric|min:0',
            'sort' => 'nullable|in:recovered_desc,recovered_asc,rate_desc,rate_asc,remaining_desc,customer_asc,branch_asc',
        ]);

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->startOfMonth();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfDay();

        $branch = trim((string) $request->get('branch', ''));
        $q = trim((string) $request->get('q', ''));
        $minRecovered = $request->filled('min_recovered')
            ? (float) $request->get('min_recovered')
            : null;

        $sort = (string) $request->get('sort', 'recovered_desc');

        /*
        |--------------------------------------------------------------------------
        | Base overdue customers
        |--------------------------------------------------------------------------
        | We include active customers who still have remaining debt.
        */
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
            'amount_local',
            'amount',
            'paid_local',
            'paid',
            'status',
        ]);

        $creditIds = $credits->pluck('source_id')
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Payments in selected period
        |--------------------------------------------------------------------------
        | Get aggregated recovery by credit_source_id in one query.
        */
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

            $netRecovered = round((float) ($paymentAgg->net_recovered ?? 0), 2);
            $grossRecovered = round((float) ($paymentAgg->gross_recovered ?? 0), 2);
            $changeTotal = round((float) ($paymentAgg->change_total ?? 0), 2);
            $paymentCount = (int) ($paymentAgg->payment_count ?? 0);
            $lastPaymentAt = !empty($paymentAgg?->last_payment_at)
                ? Carbon::parse($paymentAgg->last_payment_at)
                : null;

            if ($minRecovered !== null && $netRecovered < $minRecovered) {
                continue;
            }

            $recoveryRate = $total > 0
                ? round(($netRecovered / $total) * 100, 2)
                : 0;

            $rows->push((object) [
                'source_id' => $credit->source_id,
                'logicalref' => $credit->logicalref,
                'branch' => $credit->branch,
                'name' => $credit->name,
                'phone' => $credit->phone,
                'passport' => $credit->passport,
                'contract' => $credit->contract,
                'clientref' => $credit->clientref,
                'credit_date' => $credit->date_ ? Carbon::parse($credit->date_) : null,
                'status' => $credit->status,

                'total' => round($total, 2),
                'paid' => round($paid, 2),
                'remaining' => $remaining,

                'payment_count' => $paymentCount,
                'gross_recovered' => $grossRecovered,
                'change_total' => $changeTotal,
                'net_recovered' => $netRecovered,
                'recovery_rate' => $recoveryRate,
                'last_payment_at' => $lastPaymentAt,
                'is_recovered' => $netRecovered > 0,
            ]);
        }

        $rows = match ($sort) {
            'recovered_asc' => $rows->sortBy('net_recovered'),
            'rate_desc' => $rows->sortByDesc('recovery_rate'),
            'rate_asc' => $rows->sortBy('recovery_rate'),
            'remaining_desc' => $rows->sortByDesc('remaining'),
            'customer_asc' => $rows->sortBy('name'),
            'branch_asc' => $rows->sortBy('branch'),
            default => $rows->sortByDesc('net_recovered'),
        };

        $rows = $rows->values();

        $summary = [
            'overdue_customers' => $rows->count(),
            'recovered_customers' => $rows->where('is_recovered', true)->count(),
            'not_recovered_customers' => $rows->where('is_recovered', false)->count(),
            'total_debt' => round((float) $rows->sum('total'), 2),
            'total_remaining' => round((float) $rows->sum('remaining'), 2),
            'gross_recovered' => round((float) $rows->sum('gross_recovered'), 2),
            'change_total' => round((float) $rows->sum('change_total'), 2),
            'net_recovered' => round((float) $rows->sum('net_recovered'), 2),
        ];

        $summary['customer_recovery_rate'] = $summary['overdue_customers'] > 0
            ? round(($summary['recovered_customers'] / $summary['overdue_customers']) * 100, 2)
            : 0;

        $summary['amount_recovery_rate'] = $summary['total_debt'] > 0
            ? round(($summary['net_recovered'] / $summary['total_debt']) * 100, 2)
            : 0;

        $branchRows = $rows
            ->groupBy(fn ($row) => $row->branch ?: 'N/A')
            ->map(function ($items, $branchName) {
                $count = $items->count();
                $recoveredCount = $items->where('is_recovered', true)->count();
                $totalDebt = round((float) $items->sum('total'), 2);
                $netRecovered = round((float) $items->sum('net_recovered'), 2);

                return (object) [
                    'branch_name' => $branchName,
                    'customers' => $count,
                    'recovered_customers' => $recoveredCount,
                    'customer_recovery_rate' => $count > 0 ? round(($recoveredCount / $count) * 100, 2) : 0,
                    'total_debt' => $totalDebt,
                    'net_recovered' => $netRecovered,
                    'amount_recovery_rate' => $totalDebt > 0 ? round(($netRecovered / $totalDebt) * 100, 2) : 0,
                ];
            })
            ->sortByDesc('net_recovered')
            ->values();

        $perPage = 50;
        $page = LengthAwarePaginator::resolveCurrentPage();

        $paginatedRows = new LengthAwarePaginator(
            $rows->slice(($page - 1) * $perPage, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $branches = Credit::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch');

        return view('pages.reports.recovery-effectiveness.index', [
            'rows' => $paginatedRows,
            'branchRows' => $branchRows,
            'summary' => $summary,
            'branches' => $branches,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'branch' => $branch,
            'q' => $q,
            'minRecovered' => $minRecovered,
            'sort' => $sort,
        ]);
    }
}