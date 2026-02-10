<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Models\CreditPayment;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $role = auth()->user()->role;

        if ($role === UserRoleEnum::CASHIER) {

            $start = now()->startOfDay();
            $end   = now()->endOfDay();

            $base = CreditPayment::query()
                ->forCashier(auth()->id()); // ✅ sadece kendi

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

        // Admin & diğerleri -> mevcut dashboard
        return view('pages.dashboard.dashboard');
    }
}
