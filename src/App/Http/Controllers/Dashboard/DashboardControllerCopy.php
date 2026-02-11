<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Models\CreditPayment;

class DashboardControllerCopy extends Controller
{
    public function __invoke()
    {
        $role = auth()->user()->role;

        if ($role === UserRoleEnum::CASHIER) {

            $start = now()->startOfDay();
            $end   = now()->endOfDay();

            $base = CreditPayment::query()->forCashier(auth()->id());

            $today = (clone $base)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as today_total')
                ->selectRaw('COUNT(*) as today_count')
                ->selectRaw('COALESCE(AVG(pay_amount - COALESCE(change_amount, 0)), 0) as avg_payment')
                ->selectRaw('MAX(created_at) as last_payment_at')
                ->first();

            $stats = [
                'today_total' => (float) ($today->today_total ?? 0),
                'today_count' => (int) ($today->today_count ?? 0),
                'avg_payment' => (float) ($today->avg_payment ?? 0),
                'last_payment_at' => $today->last_payment_at ?? null,
            ];

            $recentPayments = (clone $base)
                ->orderByDesc('id')
                ->limit(10)
                ->get([
                    'pay_amount',
                    'change_amount',
                    'created_at',
                    'customer_name',
                    'customer_phone',
                ])
                ->map(function ($p) {
                    $p->amount = $p->applied_amount;
                    return $p;
                });

            return view('pages.dashboard.cashier', compact('stats', 'recentPayments'));
        }

        // ✅ ADMIN (dashboard.blade.php)
        $start = now()->startOfDay();
        $end   = now()->endOfDay();

        $base = CreditPayment::query()
            ->whereBetween('created_at', [$start, $end]);

        $row = (clone $base)
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net')
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(AVG(pay_amount - COALESCE(change_amount,0)),0) as avg_net')
            ->selectRaw('COALESCE(SUM(COALESCE(cash_amount,0)),0) as total_cash')
            ->selectRaw('COALESCE(SUM(COALESCE(card_amount,0)),0) as total_card')
            ->selectRaw('COALESCE(SUM(CASE WHEN method = \'mixed\' THEN (pay_amount - COALESCE(change_amount,0)) ELSE 0 END),0) as total_mixed')
            ->selectRaw('MAX(created_at) as last_payment_at')
            ->first();

        $kpi = [
            'total_net' => (float)($row->total_net ?? 0),
            'total_count' => (int)($row->total_count ?? 0),
            'avg_net' => (float)($row->avg_net ?? 0),
            'total_cash' => (float)($row->total_cash ?? 0),
            'total_card' => (float)($row->total_card ?? 0),
            'total_mixed' => (float)($row->total_mixed ?? 0),
            'last_payment_at' => $row->last_payment_at ?? null,
        ];

        // Son 7 gün net (Sales Forecast chart)
        $days = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->startOfDay());

        $dailyRows = CreditPayment::query()
            ->whereBetween('created_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->selectRaw("DATE(created_at) as d, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net")
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $dailyMap = $dailyRows->pluck('total_net', 'd');

        $chartDaily = [
            'labels' => $days->map(fn($d) => $d->format('d.m'))->values(),
            'series' => $days->map(function ($d) use ($dailyMap) {
                $key = $d->toDateString();
                return (float)($dailyMap[$key] ?? 0);
            })->values(),
        ];

        // Cash vs Card (Balance Overview chart)
        $chartCashCard = [
            'cash' => $kpi['total_cash'],
            'card' => $kpi['total_card'],
        ];

        return view('pages.dashboard.dashboard', compact('kpi', 'chartDaily', 'chartCashCard'));
    }
}