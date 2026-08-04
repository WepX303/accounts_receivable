<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class OverduePaymentsReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'branch' => 'nullable|string|max:100',
            'min_overdue' => 'nullable|numeric|min:0',
            'min_days' => 'nullable|integer|min:0',
            'sort' => 'nullable|in:overdue_desc,overdue_asc,days_desc,days_asc,customer_asc,branch_asc',
        ]);

        $today = now()->startOfDay();
        $todaySql = $today->toDateString();

        $q = trim((string) $request->get('q', ''));
        $branch = trim((string) $request->get('branch', ''));
        $minOverdue = $request->filled('min_overdue') ? (float) $request->get('min_overdue') : null;
        $minDays = $request->filled('min_days') ? (int) $request->get('min_days') : null;
        $sort = (string) $request->get('sort', 'overdue_desc');

        // Keyed by the viewer's branch access — the list is scoped, so one cache
        // shared across users would hand out branches they cannot open.
        $branchCacheKey = 'overdue_report:branches:' . (auth()->user()?->branchCacheKey() ?? 'guest');

        $branches = Cache::remember($branchCacheKey, now()->addMinutes(30), function () {
            return Credit::query()
                ->whereNotNull('branch')
                ->where('branch', '!=', '')
                ->distinct()
                ->orderBy('branch')
                ->pluck('branch');
        });

        $query = Credit::query()
            ->where('active', true)
            ->where('is_blocked', 0)
            ->whereNotNull('date_')
            ->whereRaw('COALESCE(amount_local, amount, 0) > 0')
            ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)')
            ->whereRaw("
                (
                    LEAST(GREATEST(FLOOR((?::date - date_::date) / 30), 0), 6)
                    * ROUND((COALESCE(amount_local, amount, 0) / 6)::numeric, 2)
                ) > COALESCE(paid_local, paid, 0)
            ", [$todaySql]);

        if ($q !== '') {
            $like = '%' . $q . '%';

            $query->where(function ($sub) use ($like) {
                $sub->where('name', 'ilike', $like)
                    ->orWhere('phone', 'ilike', $like)
                    ->orWhere('passport', 'ilike', $like)
                    ->orWhere('contract', 'ilike', $like)
                    ->orWhere('clientref', 'ilike', $like);
            });
        }

        if ($branch !== '') {
            $query->where('branch', $branch);
        }

        $credits = $query
            ->get([
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
                'willpaiddate',
                'status',
            ]);

        $rows = collect();

        foreach ($credits as $credit) {
            $total = (float) ($credit->amount_local ?? $credit->amount ?? 0);
            $paid = (float) ($credit->paid_local ?? $credit->paid ?? 0);

            if ($total <= 0) {
                continue;
            }

            $monthly = round($total / 6, 2);
            $creditDate = Carbon::parse($credit->date_)->startOfDay();

            $dueInstallmentCount = 0;
            $lastDueDate = null;

            for ($i = 1; $i <= 6; $i++) {
                $dueDate = $creditDate->copy()->addMonthsNoOverflow($i)->startOfDay();

                if ($today->gte($dueDate)) {
                    $dueInstallmentCount = $i;
                    $lastDueDate = $dueDate;
                }
            }

            if ($dueInstallmentCount <= 0) {
                continue;
            }

            $expectedPaid = round(min($monthly * $dueInstallmentCount, $total), 2);
            $overdueAmount = round(max($expectedPaid - $paid, 0), 2);

            // Do not show small overdue amounts under 10 TMT
            if ($overdueAmount < 10) {
                continue;
            }

            $overdueDays = $lastDueDate ? $lastDueDate->diffInDays($today) : 0;

            if ($minOverdue !== null && $overdueAmount < $minOverdue) {
                continue;
            }

            if ($minDays !== null && $overdueDays < $minDays) {
                continue;
            }

            $rows->push((object) [
                'source_id' => $credit->source_id,
                'logicalref' => $credit->logicalref,
                'branch' => $credit->branch,
                'name' => $credit->name,
                'phone' => $credit->phone,
                'passport' => $credit->passport,
                'contract' => $credit->contract,
                'clientref' => $credit->clientref,
                'credit_date' => $creditDate,
                'last_due_date' => $lastDueDate,
                'total' => round($total, 2),
                'paid' => round($paid, 2),
                'remaining' => round(max($total - $paid, 0), 2),
                'monthly' => $monthly,
                'due_installment_count' => $dueInstallmentCount,
                'expected_paid' => $expectedPaid,
                'overdue_amount' => $overdueAmount,
                'overdue_days' => $overdueDays,
                'status' => $credit->status,
                'willpaiddate' => $credit->willpaiddate,
            ]);
        }

        $rows = match ($sort) {
            'overdue_asc' => $rows->sortBy('overdue_amount'),
            'days_desc' => $rows->sortByDesc('overdue_days'),
            'days_asc' => $rows->sortBy('overdue_days'),
            'customer_asc' => $rows->sortBy('name'),
            'branch_asc' => $rows->sortBy('branch'),
            default => $rows->sortByDesc('overdue_amount'),
        };

        $rows = $rows->values();

        $summary = [
            'customers_count' => $rows->count(),
            'total_debt' => round((float) $rows->sum('total'), 2),
            'total_paid' => round((float) $rows->sum('paid'), 2),
            'total_remaining' => round((float) $rows->sum('remaining'), 2),
            'total_expected' => round((float) $rows->sum('expected_paid'), 2),
            'total_overdue' => round((float) $rows->sum('overdue_amount'), 2),
        ];

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

        return view('pages.reports.overdue-payments.index', [
            'rows' => $paginatedRows,
            'summary' => $summary,
            'today' => $today,
            'branches' => $branches,
            'q' => $q,
            'branch' => $branch,
            'minOverdue' => $minOverdue,
            'minDays' => $minDays,
            'sort' => $sort,
        ]);
    }
}
