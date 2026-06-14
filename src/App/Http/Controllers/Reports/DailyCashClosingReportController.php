<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyCashClosingReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'branch' => 'nullable|string|max:100',
            'cashier' => 'nullable|string|max:255',
        ]);

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfDay();

        $branch = trim((string) $request->get('branch', ''));
        $cashier = trim((string) $request->get('cashier', ''));

        $base = CreditPayment::query()
            ->notVoided()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($branch !== '', fn ($q) => $q->where('branch', $branch))
            ->when($cashier !== '', fn ($q) => $q->where('created_by_name', $cashier));

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

        $dailyRows = (clone $base)
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
            ->get();

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

        return view('pages.reports.daily-cash-closing.index', [
            'summary' => $summary,
            'dailyRows' => $dailyRows,
            'branches' => $branches,
            'cashiers' => $cashiers,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'branch' => $branch,
            'cashier' => $cashier,
        ]);
    }
}