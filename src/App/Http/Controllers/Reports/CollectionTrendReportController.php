<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionTrendReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'branch' => 'nullable|string|max:100',
            'cashier' => 'nullable|string|max:255',
            'group_by' => 'nullable|in:day,week,month',
        ]);

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->subDays(30)->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfDay();

        $branch = trim((string) $request->get('branch', ''));
        $cashier = trim((string) $request->get('cashier', ''));
        $groupBy = (string) $request->get('group_by', 'day');

        $base = CreditPayment::query()
            ->notVoided()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($branch !== '', fn ($q) => $q->where('branch', $branch))
            ->when($cashier !== '', fn ($q) => $q->where('created_by_name', $cashier));

        $periodSql = match ($groupBy) {
            'week' => "TO_CHAR(DATE_TRUNC('week', created_at), 'YYYY-MM-DD')",
            'month' => "TO_CHAR(DATE_TRUNC('month', created_at), 'YYYY-MM')",
            default => "TO_CHAR(created_at::date, 'YYYY-MM-DD')",
        };

        $trendRows = (clone $base)
            ->selectRaw("$periodSql as period_key")
            ->selectRaw('COUNT(*) as tx_count')
            ->selectRaw('COALESCE(SUM(pay_amount), 0) as gross_total')
            ->selectRaw('COALESCE(SUM(change_amount), 0) as change_total')
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as net_total')
            ->selectRaw('COALESCE(SUM(cash_amount), 0) as cash_total')
            ->selectRaw('COALESCE(SUM(card_amount), 0) as card_total')
            ->selectRaw('COALESCE(SUM(phone_amount), 0) as phone_total')
            ->groupBy(DB::raw($periodSql))
            ->orderBy('period_key')
            ->get();

        $summaryRow = (clone $base)
            ->selectRaw('COUNT(*) as tx_count')
            ->selectRaw('COALESCE(SUM(pay_amount), 0) as gross_total')
            ->selectRaw('COALESCE(SUM(change_amount), 0) as change_total')
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as net_total')
            ->selectRaw('COALESCE(SUM(cash_amount), 0) as cash_total')
            ->selectRaw('COALESCE(SUM(card_amount), 0) as card_total')
            ->selectRaw('COALESCE(SUM(phone_amount), 0) as phone_total')
            ->first();

        $summary = [
            'tx_count' => (int) ($summaryRow->tx_count ?? 0),
            'gross_total' => (float) ($summaryRow->gross_total ?? 0),
            'change_total' => (float) ($summaryRow->change_total ?? 0),
            'net_total' => (float) ($summaryRow->net_total ?? 0),
            'cash_total' => (float) ($summaryRow->cash_total ?? 0),
            'card_total' => (float) ($summaryRow->card_total ?? 0),
            'phone_total' => (float) ($summaryRow->phone_total ?? 0),
        ];

        $bestRow = $trendRows->sortByDesc('net_total')->first();
        $lowestRow = $trendRows->where('net_total', '>', 0)->sortBy('net_total')->first();

        $summary['avg_net'] = $trendRows->count() > 0
            ? round((float) $trendRows->avg('net_total'), 2)
            : 0;

        $branches = CreditPayment::query()
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch');

        $cashiers = CreditPayment::query()
            ->whereNotNull('created_by_name')
            ->where('created_by_name', '!=', '')
            ->distinct()
            ->orderBy('created_by_name')
            ->pluck('created_by_name');

        $chart = [
            'labels' => $trendRows->pluck('period_key')->values(),
            'net' => $trendRows->pluck('net_total')->map(fn ($v) => round((float) $v, 2))->values(),
            'gross' => $trendRows->pluck('gross_total')->map(fn ($v) => round((float) $v, 2))->values(),
            'cash' => $trendRows->pluck('cash_total')->map(fn ($v) => round((float) $v, 2))->values(),
            'card' => $trendRows->pluck('card_total')->map(fn ($v) => round((float) $v, 2))->values(),
            'phone' => $trendRows->pluck('phone_total')->map(fn ($v) => round((float) $v, 2))->values(),
        ];

        return view('pages.reports.collection-trend.index', [
            'summary' => $summary,
            'trendRows' => $trendRows,
            'bestRow' => $bestRow,
            'lowestRow' => $lowestRow,
            'branches' => $branches,
            'cashiers' => $cashiers,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'branch' => $branch,
            'cashier' => $cashier,
            'groupBy' => $groupBy,
            'chart' => $chart,
        ]);
    }
}