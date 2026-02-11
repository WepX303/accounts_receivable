<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Models\CreditPayment;
use Illuminate\Http\Request;

// class DashboardController extends Controller
// {
//     public function __invoke(Request $request)
//     {
//         $role = auth()->user()->role;

//         // =========================
//         // CASHIER
//         // =========================
//         if ($role === UserRoleEnum::CASHIER) {

//             $start = now()->startOfDay();
//             $end   = now()->endOfDay();

//             $base = CreditPayment::query()->forCashier(auth()->id()); // ✅ sadece kendi

//             $today = (clone $base)
//                 ->whereBetween('created_at', [$start, $end])
//                 ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as today_total')
//                 ->selectRaw('COUNT(*) as today_count')
//                 ->selectRaw('COALESCE(AVG(pay_amount - COALESCE(change_amount, 0)), 0) as avg_payment')
//                 ->selectRaw('MAX(created_at) as last_payment_at')
//                 ->first();

//             $stats = [
//                 'today_total' => (float) ($today->today_total ?? 0),
//                 'today_count' => (int) ($today->today_count ?? 0),
//                 'avg_payment' => (float) ($today->avg_payment ?? 0),
//                 'last_payment_at' => $today->last_payment_at ?? null,
//             ];

//             $recentPayments = (clone $base)
//                 ->orderByDesc('id')
//                 ->limit(10)
//                 ->get([
//                     'pay_amount',
//                     'change_amount',
//                     'created_at',
//                     'customer_name',
//                     'customer_phone',
//                 ])
//                 ->map(function ($p) {
//                     $p->amount = $p->applied_amount;
//                     return $p;
//                 });

//             return view('pages.dashboard.cashier', compact('stats', 'recentPayments'));
//         }

//         // =========================
//         // ADMIN (dashboard.blade.php)
//         // =========================
//         $period = $request->get('period', 'today');

//         $start = now()->startOfDay();
//         $end   = now()->endOfDay();

//         switch ($period) {
//             case 'today':
//                 $start = now()->startOfDay();
//                 $end   = now()->endOfDay();
//                 break;

//             case 'week': // bu hafta (pazartesi->bugün)
//                 $start = now()->startOfWeek()->startOfDay();
//                 $end   = now()->endOfDay();
//                 break;

//             case 'month': // bu ay (ayın 1'i->bugün)
//                 $start = now()->startOfMonth()->startOfDay();
//                 $end   = now()->endOfDay();
//                 break;

//             case 'last7': // son 7 gün
//                 $start = now()->subDays(6)->startOfDay();
//                 $end   = now()->endOfDay();
//                 break;

//             case 'last30': // son 30 gün
//                 $start = now()->subDays(29)->startOfDay();
//                 $end   = now()->endOfDay();
//                 break;

//             default:
//                 // bilinmeyen değer gelirse today'e dön
//                 $period = 'today';
//                 $start = now()->startOfDay();
//                 $end   = now()->endOfDay();
//                 break;
//         }

//         $base = CreditPayment::query()
//             ->whereBetween('created_at', [$start, $end]);

//         $row = (clone $base)
//             ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net')
//             ->selectRaw('COUNT(*) as total_count')
//             ->selectRaw('COALESCE(AVG(pay_amount - COALESCE(change_amount,0)),0) as avg_net')
//             ->selectRaw('COALESCE(SUM(COALESCE(cash_amount,0)),0) as total_cash')
//             ->selectRaw('COALESCE(SUM(COALESCE(card_amount,0)),0) as total_card')
//             ->selectRaw('COALESCE(SUM(CASE WHEN method = \'mixed\' THEN (pay_amount - COALESCE(change_amount,0)) ELSE 0 END),0) as total_mixed')
//             ->selectRaw('MAX(created_at) as last_payment_at')
//             ->first();

//         $kpi = [
//             'total_net' => (float)($row->total_net ?? 0),
//             'total_count' => (int)($row->total_count ?? 0),
//             'avg_net' => (float)($row->avg_net ?? 0),
//             'total_cash' => (float)($row->total_cash ?? 0),
//             'total_card' => (float)($row->total_card ?? 0),
//             'total_mixed' => (float)($row->total_mixed ?? 0),
//             'last_payment_at' => $row->last_payment_at ?? null,
//         ];

//         // Chart: Sales Forecast alanı için gün gün net tahsilat
//         // today/week/month seçilse bile chart'ı "son 7 gün" gösterelim (istersen sonra period'e göre büyütürüz)
//         $chartDaysCount = in_array($period, ['last30']) ? 30 : 7;

//         $chartStart = now()->subDays($chartDaysCount - 1)->startOfDay();
//         $chartEnd   = now()->endOfDay();

//         $days = collect(range($chartDaysCount - 1, 0))
//             ->map(fn($i) => now()->subDays($i)->startOfDay());

//         $dailyRows = CreditPayment::query()
//             ->whereBetween('created_at', [$chartStart, $chartEnd])
//             ->selectRaw("DATE(created_at) as d, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net")
//             ->groupBy('d')
//             ->orderBy('d')
//             ->get();

//         $dailyMap = $dailyRows->pluck('total_net', 'd');

//         $chartDaily = [
//             'labels' => $days->map(fn($d) => $d->format('d.m'))->values(),
//             'series' => $days->map(fn($d) => (float)($dailyMap[$d->toDateString()] ?? 0))->values(),
//         ];

//         // Cash vs Card (Balance Overview chart)
//         $chartCashCard = [
//             'cash' => $kpi['total_cash'],
//             'card' => $kpi['total_card'],
//         ];

//         return view('pages.dashboard.dashboard', compact(
//             'kpi',
//             'chartDaily',
//             'chartCashCard',
//             'period',
//             'start',
//             'end'
//         ));
//     }
// }







// class DashboardController extends Controller
// {
//     public function __invoke(Request $request)
//     {
//         $role = auth()->user()->role;

//         // =========================
//         // CASHIER
//         // =========================
//         if ($role === UserRoleEnum::CASHIER) {

//             $start = now()->startOfDay();
//             $end   = now()->endOfDay();

//             $base = CreditPayment::query()->forCashier(auth()->id()); // ✅ sadece kendi

//             $today = (clone $base)
//                 ->whereBetween('created_at', [$start, $end])
//                 ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as today_total')
//                 ->selectRaw('COUNT(*) as today_count')
//                 ->selectRaw('COALESCE(AVG(pay_amount - COALESCE(change_amount, 0)), 0) as avg_payment')
//                 ->selectRaw('MAX(created_at) as last_payment_at')
//                 ->first();

//             $stats = [
//                 'today_total' => (float) ($today->today_total ?? 0),
//                 'today_count' => (int) ($today->today_count ?? 0),
//                 'avg_payment' => (float) ($today->avg_payment ?? 0),
//                 'last_payment_at' => $today->last_payment_at ?? null,
//             ];

//             $recentPayments = (clone $base)
//                 ->orderByDesc('id')
//                 ->limit(10)
//                 ->get([
//                     'pay_amount',
//                     'change_amount',
//                     'created_at',
//                     'customer_name',
//                     'customer_phone',
//                 ])
//                 ->map(function ($p) {
//                     $p->amount = $p->applied_amount;
//                     return $p;
//                 });

//             return view('pages.dashboard.cashier', compact('stats', 'recentPayments'));
//         }

//         // =========================
//         // ADMIN (dashboard.blade.php)
//         // =========================
//         $period = $request->get('period', 'today');

//         $start = now()->startOfDay();
//         $end   = now()->endOfDay();

//         switch ($period) {
//             case 'today':
//                 $start = now()->startOfDay();
//                 $end   = now()->endOfDay();
//                 break;

//             case 'yesterday':
//                 $start = now()->subDay()->startOfDay();
//                 $end   = now()->subDay()->endOfDay();
//                 break;

//             case 'week': // bu hafta (pazartesi->bugün)
//                 $start = now()->startOfWeek()->startOfDay();
//                 $end   = now()->endOfDay();
//                 break;

//             case 'month': // bu ay (ayın 1'i->bugün)
//                 $start = now()->startOfMonth()->startOfDay();
//                 $end   = now()->endOfDay();
//                 break;

//             case 'last7': // son 7 gün
//                 $start = now()->subDays(6)->startOfDay();
//                 $end   = now()->endOfDay();
//                 break;

//             case 'last30': // son 30 gün
//                 $start = now()->subDays(29)->startOfDay();
//                 $end   = now()->endOfDay();
//                 break;

//             default:
//                 // bilinmeyen değer gelirse today'e dön
//                 $period = 'today';
//                 $start = now()->startOfDay();
//                 $end   = now()->endOfDay();
//                 break;
//         }

//         $base = CreditPayment::query()
//             ->whereBetween('created_at', [$start, $end]);

//         $row = (clone $base)
//             ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net')
//             ->selectRaw('COUNT(*) as total_count')
//             ->selectRaw('COALESCE(AVG(pay_amount - COALESCE(change_amount,0)),0) as avg_net')
//             ->selectRaw('COALESCE(SUM(COALESCE(cash_amount,0)),0) as total_cash')
//             ->selectRaw('COALESCE(SUM(COALESCE(card_amount,0)),0) as total_card')
//             ->selectRaw('COALESCE(SUM(CASE WHEN method = \'mixed\' THEN (pay_amount - COALESCE(change_amount,0)) ELSE 0 END),0) as total_mixed')
//             ->selectRaw('MAX(created_at) as last_payment_at')
//             ->first();

//         $kpi = [
//             'total_net' => (float)($row->total_net ?? 0),
//             'total_count' => (int)($row->total_count ?? 0),
//             'avg_net' => (float)($row->avg_net ?? 0),
//             'total_cash' => (float)($row->total_cash ?? 0),
//             'total_card' => (float)($row->total_card ?? 0),
//             'total_mixed' => (float)($row->total_mixed ?? 0),
//             'last_payment_at' => $row->last_payment_at ?? null,
//         ];

//         // Chart: Sales Forecast alanı için gün gün net tahsilat
//         // today/week/month seçilse bile chart'ı "son 7 gün" gösterelim (istersen sonra period'e göre büyütürüz)
//         $chartDaysCount = in_array($period, ['last30']) ? 30 : 7;



//         $chartStart = now()->subDays($chartDaysCount - 1)->startOfDay();
//         $chartEnd   = now()->endOfDay();

//         $days = collect(range($chartDaysCount - 1, 0))
//             ->map(fn($i) => now()->subDays($i)->startOfDay());

//         $dailyRows = CreditPayment::query()
//             ->whereBetween('created_at', [$chartStart, $chartEnd])
//             ->selectRaw("DATE(created_at) as d, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net")
//             ->groupBy('d')
//             ->orderBy('d')
//             ->get();

//         $dailyMap = $dailyRows->pluck('total_net', 'd');

//         $chartDaily = [
//             'labels' => $days->map(fn($d) => $d->format('d.m'))->values(),
//             'series' => $days->map(fn($d) => (float)($dailyMap[$d->toDateString()] ?? 0))->values(),
//         ];

//         // Cash vs Card (Balance Overview chart)
//         $chartCashCard = [
//             'cash' => $kpi['total_cash'],
//             'card' => $kpi['total_card'],
//         ];

//         return view('pages.dashboard.dashboard', compact(
//             'kpi',
//             'chartDaily',
//             'chartCashCard',
//             'period',
//             'start',
//             'end'
//         ));
//     }
// }




class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $role = auth()->user()->role;

        // =========================
        // CASHIER
        // =========================
        if ($role === UserRoleEnum::CASHIER) {

            $start = now()->startOfDay();
            $end   = now()->endOfDay();

            $base = CreditPayment::query()->forCashier(auth()->id()); // ✅ sadece kendi

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

        // =========================
        // ADMIN (dashboard.blade.php)
        // =========================
        $period = $request->get('period', 'today');

        $start = now()->startOfDay();
        $end   = now()->endOfDay();

        switch ($period) {
            case 'today':
                $start = now()->startOfDay();
                $end   = now()->endOfDay();
                break;

            case 'yesterday':
                $start = now()->subDay()->startOfDay();
                $end   = now()->subDay()->endOfDay();
                break;

            case 'week': // bu hafta (pazartesi->bugün)
                $start = now()->startOfWeek()->startOfDay();
                $end   = now()->endOfDay();
                break;

            case 'month': // bu ay (ayın 1'i->bugün)
                $start = now()->startOfMonth()->startOfDay();
                $end   = now()->endOfDay();
                break;

            case 'last7': // son 7 gün
                $start = now()->subDays(6)->startOfDay();
                $end   = now()->endOfDay();
                break;

            case 'last30': // son 30 gün
                $start = now()->subDays(29)->startOfDay();
                $end   = now()->endOfDay();
                break;

            default:
                $period = 'today';
                $start = now()->startOfDay();
                $end   = now()->endOfDay();
                break;
        }

        $base = CreditPayment::query()
            ->whereBetween('created_at', [$start, $end]);

        // =========================
        // KPI
        // =========================
        $row = (clone $base)
            ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net')
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(AVG(pay_amount - COALESCE(change_amount,0)),0) as avg_net')
            ->selectRaw('COALESCE(SUM(COALESCE(cash_amount,0)),0) as total_cash')
            ->selectRaw('COALESCE(SUM(COALESCE(card_amount,0)),0) as total_card')
            ->selectRaw('COALESCE(SUM(CASE WHEN method = \'mixed\' THEN (pay_amount - COALESCE(change_amount,0)) ELSE 0 END),0) as total_mixed')
            ->selectRaw("COALESCE(SUM(CASE WHEN method = 'phone' THEN (pay_amount - COALESCE(change_amount,0)) ELSE 0 END),0) as total_phone")
            ->selectRaw('MAX(created_at) as last_payment_at')
            ->first();

        $kpi = [
            'total_net' => (float)($row->total_net ?? 0),
            'total_count' => (int)($row->total_count ?? 0),
            'avg_net' => (float)($row->avg_net ?? 0),
            'total_cash' => (float)($row->total_cash ?? 0),
            'total_card' => (float)($row->total_card ?? 0),
            'total_mixed' => (float)($row->total_mixed ?? 0),
            'total_phone' => (float)($row->total_phone ?? 0),
            'last_payment_at' => $row->last_payment_at ?? null,
        ];


        $byCashier = (clone $base)
            ->selectRaw("
        created_by as cashier_id,
        COALESCE(created_by_name, 'N/A') as cashier_name,
        COUNT(*) as tx_count,
        COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net,
        COALESCE(SUM(COALESCE(cash_amount,0)),0) as total_cash,
        COALESCE(SUM(COALESCE(card_amount,0)),0) as total_card")
            ->groupBy('cashier_id', 'cashier_name')
            ->orderByDesc('total_net')
            ->limit(12)
            ->get();

        $topCashier = $byCashier->first();


        $byBranch = (clone $base)
            ->selectRaw("
        COALESCE(branch, 'N/A') as branch,
        COUNT(*) as tx_count,
        COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net,
        COALESCE(SUM(COALESCE(cash_amount,0)),0) as total_cash,
        COALESCE(SUM(COALESCE(card_amount,0)),0) as total_card,
        COALESCE(SUM(CASE WHEN method = 'phone' THEN (pay_amount - COALESCE(change_amount,0)) ELSE 0 END),0) as total_phone
    ")
            ->groupBy('branch')
            ->orderByDesc('total_net')
            ->limit(20)
            ->get();


        // =========================
        // CHART (period'a göre)
        // =========================
        $chartDaily = [
            'labels' => [],
            'series' => [],
        ];

        if ($period === 'today') {
            // Saatlik
            $hours = collect(range(0, 23));

            $rows = CreditPayment::query()
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw("EXTRACT(HOUR FROM created_at) as h, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net")
                ->groupBy('h')
                ->pluck('total_net', 'h');

            $chartDaily = [
                'labels' => $hours->map(fn($h) => str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':00')->values(),
                'series' => $hours->map(fn($h) => (float)($rows[$h] ?? 0))->values(),
            ];
        } elseif (in_array($period, ['week', 'last7'], true)) {
            // Son 7 gün
            $days = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->startOfDay());

            $rows = CreditPayment::query()
                ->whereBetween('created_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
                ->selectRaw("DATE(created_at) as d, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net")
                ->groupBy('d')
                ->pluck('total_net', 'd');

            $chartDaily = [
                'labels' => $days->map(fn($d) => $d->format('d.m'))->values(),
                'series' => $days->map(fn($d) => (float)($rows[$d->toDateString()] ?? 0))->values(),
            ];
        } elseif ($period === 'month') {
            // Ayın 1'inden bugüne (gün gün)
            $daysInMonth = now()->day; // bugünün ay içindeki günü
            $days = collect(range(1, $daysInMonth));

            $rows = CreditPayment::query()
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw("EXTRACT(DAY FROM created_at) as d, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net")
                ->groupBy('d')
                ->pluck('total_net', 'd');

            $chartDaily = [
                'labels' => $days->map(fn($d) => str_pad((string)$d, 2, '0', STR_PAD_LEFT))->values(),
                'series' => $days->map(fn($d) => (float)($rows[$d] ?? 0))->values(),
            ];
        } elseif ($period === 'last30') {
            // Son 30 gün
            $days = collect(range(29, 0))->map(fn($i) => now()->subDays($i)->startOfDay());

            $rows = CreditPayment::query()
                ->whereBetween('created_at', [now()->subDays(29)->startOfDay(), now()->endOfDay()])
                ->selectRaw("DATE(created_at) as d, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net")
                ->groupBy('d')
                ->pluck('total_net', 'd');

            $chartDaily = [
                'labels' => $days->map(fn($d) => $d->format('d.m'))->values(),
                'series' => $days->map(fn($d) => (float)($rows[$d->toDateString()] ?? 0))->values(),
            ];
        } elseif ($period === 'yesterday') {
            // Dün (saatlik)
            $hours = collect(range(0, 23));



            $rows = CreditPayment::query()
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw("
        EXTRACT(HOUR FROM created_at) as h,
        COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net
    ")
                ->groupBy('h')
                ->pluck('total_net', 'h');

            $chartDaily = [
                'labels' => $hours->map(fn($h) => str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':00')->values(),
                'series' => $hours->map(fn($h) => (float)($rows[$h] ?? 0))->values(),
            ];
        }


        return view('pages.dashboard.dashboard', compact(
            'kpi',
            'chartDaily',
            'period',
            'start',
            'end',
            'byCashier',
            'topCashier',
            'byBranch'

        ));
    }
}
