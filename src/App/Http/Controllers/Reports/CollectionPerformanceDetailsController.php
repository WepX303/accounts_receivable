<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CollectionPerformanceDetailsController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'type' => 'required|in:cashier,branch',
            'value' => 'required|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'method' => 'nullable|in:all,cash,card,phone',
        ]);

        $type = (string) $request->get('type');
        $value = trim((string) $request->get('value'));

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->startOfDay()
            : now()->startOfDay();

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfDay();

        $method = (string) $request->get('method', 'all');

        $query = CreditPayment::query()
            ->notVoided()
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($type === 'cashier') {
            $query->where('created_by_name', $value);
        }

        if ($type === 'branch') {
            $query->where('branch', $value);
        }

        $query
            ->when($method === 'cash', fn($q) => $q->where('cash_amount', '>', 0))
            ->when($method === 'card', fn($q) => $q->where('card_amount', '>', 0))
            ->when($method === 'phone', fn($q) => $q->where('phone_amount', '>', 0));

        $summaryRow = (clone $query)
            ->selectRaw('COUNT(*) as tx_count')
            ->selectRaw('COALESCE(SUM(pay_amount), 0) as gross_total')
            ->selectRaw('COALESCE(SUM(change_amount), 0) as change_total')
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as net_total')
            ->selectRaw('COALESCE(SUM(cash_amount), 0) as cash_total')
            ->selectRaw('COALESCE(SUM(card_amount), 0) as card_total')
            ->selectRaw('COALESCE(SUM(phone_amount), 0) as phone_total')
            ->first();

        $rows = (clone $query)
            ->orderByDesc('created_at')
            ->paginate(50)
            ->appends($request->query());

        $summary = [
            'tx_count' => (int) ($summaryRow->tx_count ?? 0),
            'gross_total' => (float) ($summaryRow->gross_total ?? 0),
            'change_total' => (float) ($summaryRow->change_total ?? 0),
            'net_total' => (float) ($summaryRow->net_total ?? 0),
            'cash_total' => (float) ($summaryRow->cash_total ?? 0),
            'card_total' => (float) ($summaryRow->card_total ?? 0),
            'phone_total' => (float) ($summaryRow->phone_total ?? 0),
        ];

        return view('pages.reports.collection-performance.details', [
            'rows' => $rows,
            'summary' => $summary,
            'type' => $type,
            'value' => $value,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'method' => $method,
        ]);
    }
}
