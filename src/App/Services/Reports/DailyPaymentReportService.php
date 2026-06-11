<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyPaymentReportService
{
    public function getTodayReport(): array
    {
        $start = Carbon::today();
        $end = Carbon::tomorrow();

        $payments = DB::table('credit_payments')
            ->select(
                'branch',
                'method',
                'pay_amount',
                'cash_amount',
                'card_amount',
                'phone_amount',
                'created_at'
            )
            ->whereNull('voided_at')
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->get();

        $totalCount = $payments->count();
        $totalAmount = $payments->sum(fn ($item) => (float) $item->pay_amount);

        $methodSummary = $payments
            ->groupBy('method')
            ->map(fn ($rows) => [
                'count' => $rows->count(),
                'amount' => $rows->sum(fn ($item) => (float) $item->pay_amount),
            ])
            ->toArray();

        $branchSummary = $payments
            ->groupBy('branch')
            ->map(fn ($rows) => [
                'count' => $rows->count(),
                'amount' => $rows->sum(fn ($item) => (float) $item->pay_amount),
            ])
            ->toArray();

        return [
            'date' => $start->format('Y-m-d'),
            'total_count' => $totalCount,
            'total_amount' => $totalAmount,
            'cash_total' => $payments->sum(fn ($item) => (float) $item->cash_amount),
            'card_total' => $payments->sum(fn ($item) => (float) $item->card_amount),
            'phone_total' => $payments->sum(fn ($item) => (float) $item->phone_amount),
            'method_summary' => $methodSummary,
            'branch_summary' => $branchSummary,
        ];
    }
}