<?php

namespace App\Services\Dashboard;

use App\Models\CreditPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * The figures behind the two dashboards, shared by the panel and the API.
 */
class DashboardService
{
    public static function rules(): array
    {
        return [
            'period' => 'nullable|in:today,yesterday,week,month,last7,last30,custom,all',
            'start'  => 'nullable|date',
            'end'    => 'nullable|date|after_or_equal:start',
        ];
    }

    public static function messages(): array
    {
        return [
            'period.in' => __('validations/validations.dashboard.period_invalid'),
            'start.date' => __('validations/validations.dashboard.start_date_invalid'),
            'end.date' => __('validations/validations.dashboard.end_date_invalid'),
            'end.after_or_equal' => __('validations/validations.dashboard.end_before_start'),
        ];
    }

    /**
     * A cashier's own day and their latest payments.
     *
     * @return array{stats: array, recentPayments: \Illuminate\Support\Collection}
     */
    public function cashier(User $user): array
    {
        $start = now()->startOfDay();
        $end   = now()->endOfDay();


        $base = CreditPayment::query()
            ->notVoided()
            ->forCashier($user->id);

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

        return compact('stats', 'recentPayments');
    }

    /**
     * Turns the chosen period into the name actually used and its first and last moment.
     *
     * @return array{0: string, 1: Carbon, 2: Carbon}
     */
    public function period(string $period, ?string $from = null, ?string $to = null): array
    {
        $start = now()->startOfDay();
        $end   = now()->endOfDay();

        switch ($period) {
            case 'today': // today
                $start = now()->startOfDay();
                $end   = now()->endOfDay();
                break;

            case 'yesterday': // yesterday
                $start = now()->subDay()->startOfDay();
                $end   = now()->subDay()->endOfDay();
                break;

            case 'week': // this week (Monday to today)
                $start = now()->startOfWeek()->startOfDay();
                $end   = now()->endOfDay();
                break;

            case 'month': // this month (1st of the month -> today)
                $start = now()->startOfMonth()->startOfDay();
                $end   = now()->endOfDay();
                break;

            case 'last7': // last 7 days
                $start = now()->subDays(6)->startOfDay();
                $end   = now()->endOfDay();
                break;

            case 'last30': // last 30 days
                $start = now()->subDays(29)->startOfDay();
                $end   = now()->endOfDay();
                break;

            case 'custom': // custom
                if (filled($from)) {
                    $start = Carbon::parse($from)->startOfDay();
                }
                if (filled($to)) {
                    $end = Carbon::parse($to)->endOfDay();
                }
                break;
            case 'all':
                $firstPaymentDate = CreditPayment::query()
                    ->notVoided()
                    ->min('created_at');

                $start = $firstPaymentDate
                    ? Carbon::parse($firstPaymentDate)->startOfDay()
                    : now()->startOfDay();

                $end = now()->endOfDay();
                break;

            default: // default period today
                $period = 'today';
                $start = now()->startOfDay();
                $end   = now()->endOfDay();
                break;
        }

        return [$period, $start, $end];
    }

    /**
     * Everything the management dashboard shows for a period, limited to the
     * user's branches by the model scope.
     *
     * @return array{kpi: array, byCashier: mixed, topCashier: mixed, byBranch: mixed, recentAdminPayments: mixed, chartDaily: array}
     */
    public function overview(User $user, string $period, Carbon $start, Carbon $end): array
    {
        $dashV = (int) Cache::get('admin_dashboard:v', 1);

        $cacheKey = 'admin_dashboard:' . md5(json_encode([
            'v' => $dashV,
            'period' => $period,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            // The figures below are branch-scoped, so the viewer's access has to
            // be part of the key or the first request would poison the rest.
            'branches' => $user->branchCacheKey(),
        ]));

        $data = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($start, $end, $period) {


            $base = CreditPayment::query()
                ->notVoided()
                ->whereBetween('created_at', [$start, $end]);

            // =========================
            // KPI
            // =========================

            $row = (clone $base)
                ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net')
                ->selectRaw('COUNT(*) as total_count')
                ->selectRaw('COALESCE(AVG(pay_amount - COALESCE(change_amount,0)),0) as avg_net')
                ->selectRaw("COALESCE(SUM(cash_amount), 0) as total_cash")
                ->selectRaw("COALESCE(SUM(card_amount), 0) as total_card")
                ->selectRaw("COALESCE(SUM(phone_amount), 0) as total_phone")
                ->selectRaw("COUNT(CASE WHEN method = 'mixed' THEN 1 END) as mixed_tx_count")
                ->selectRaw('MAX(created_at) as last_payment_at')
                ->first();

            $kpi = [
                'total_net' => (float)($row->total_net ?? 0),
                'total_count' => (int)($row->total_count ?? 0),
                'avg_net' => (float)($row->avg_net ?? 0),

                'total_cash' => (float)($row->total_cash ?? 0),
                'total_card' => (float)($row->total_card ?? 0),
                'total_phone' => (float)($row->total_phone ?? 0),

                'all_total' => (float)(
                    ($row->total_cash ?? 0) +
                    ($row->total_card ?? 0) +
                    ($row->total_phone ?? 0)
                ),

                'mixed_tx_count' => (int)($row->mixed_tx_count ?? 0),

                'last_payment_at' => $row->last_payment_at ?? null,
            ];

            // =========================
            // byCashier (top 12)
            // =========================
            $byCashier = (clone $base)
                ->selectRaw("
                    created_by as cashier_id,
                    COALESCE(created_by_name, 'N/A') as cashier_name,
                    COUNT(*) as tx_count,
                    COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net,
                    COALESCE(SUM(cash_amount), 0) as total_cash,
                    COALESCE(SUM(card_amount), 0) as total_card,
                    COALESCE(SUM(phone_amount), 0) as total_phone
                ")
                ->groupBy('cashier_id', 'cashier_name')
                ->orderByDesc('total_net')
                ->limit(12)
                ->get();
            $topCashier = $byCashier->first();

            // =========================
            // byBranch (top 20)
            // =========================
            $byBranch = (clone $base)
                ->selectRaw("
                    COALESCE(branch, 'N/A') as branch,
                    COUNT(*) as tx_count,
                    COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net,
                    COALESCE(SUM(cash_amount), 0) as total_cash,
                    COALESCE(SUM(card_amount), 0) as total_card,
                    COALESCE(SUM(phone_amount), 0) as total_phone
                ")
                ->groupBy('branch')
                ->orderByDesc('total_net')
                ->limit(20)
                ->get();

            // =========================
            // Recent Payments (last 20)
            // =========================

            $recentAdminPayments = (clone $base)
                ->with(['correctedByUser:id,firstname,lastname'])
                ->orderByDesc('id')
                ->limit(20)
                ->get([
                    'id',
                    'branch',
                    'created_by_name',
                    'customer_name',
                    'customer_contract',
                    'method',
                    'pay_amount',
                    'change_amount',
                    'cash_amount',
                    'card_amount',
                    'phone_amount',
                    'created_at',
                    'corrected_by',
                    'corrected_at',
                    'correct_reason',
                ])
                ->map(function ($p) {
                    $p->net_amount = (float)$p->pay_amount - (float)($p->change_amount ?? 0);
                    return $p;
                });

            // =========================
            // CHART (by period)
            // =========================
            $chartDaily = ['labels' => [], 'series' => []];

            if ($period === 'today' || $period === 'yesterday') {
                $hours = collect(range(0, 23));

                $rows = CreditPayment::query()
                    ->notVoided()
                    ->whereBetween('created_at', [$start, $end])
                    ->selectRaw("EXTRACT(HOUR FROM created_at) as h, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net")
                    ->groupBy('h')
                    ->pluck('total_net', 'h');

                $chartDaily = [
                    'labels' => $hours->map(fn($h) => str_pad((string)$h, 2, '0', STR_PAD_LEFT) . ':00')->values(),
                    'series' => $hours->map(fn($h) => (float)($rows[$h] ?? 0))->values(),
                ];
            }

            elseif ($period === 'week') {
                $weekStart = now()->startOfWeek()->startOfDay();
                $weekEnd = now()->endOfDay();

                $days = collect();
                $cursor = $weekStart->copy();

                while ($cursor <= $weekEnd) {
                    $days->push($cursor->copy());
                    $cursor->addDay();
                }

                $rows = CreditPayment::query()
                    ->notVoided()
                    ->whereBetween('created_at', [$weekStart, $weekEnd])
                    ->selectRaw("DATE(created_at) as d, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net")
                    ->groupBy('d')
                    ->pluck('total_net', 'd');

                $chartDaily = [
                    'labels' => $days->map(fn($d) => $d->format('d.m'))->values(),
                    'series' => $days->map(fn($d) => (float)($rows[$d->toDateString()] ?? 0))->values(),
                ];
            } elseif ($period === 'last7') {
                $last7Start = now()->subDays(6)->startOfDay();
                $last7End = now()->endOfDay();

                $days = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->startOfDay());

                $rows = CreditPayment::query()
                    ->notVoided()
                    ->whereBetween('created_at', [$last7Start, $last7End])
                    ->selectRaw("DATE(created_at) as d, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net")
                    ->groupBy('d')
                    ->pluck('total_net', 'd');

                $chartDaily = [
                    'labels' => $days->map(fn($d) => $d->format('d.m'))->values(),
                    'series' => $days->map(fn($d) => (float)($rows[$d->toDateString()] ?? 0))->values(),
                ];
            } elseif ($period === 'month') {
                $daysInMonth = now()->day;
                $days = collect(range(1, $daysInMonth));

                $rows = CreditPayment::query()
                    ->notVoided()
                    ->whereBetween('created_at', [$start, $end])
                    ->selectRaw("EXTRACT(DAY FROM created_at) as d, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net")
                    ->groupBy('d')
                    ->pluck('total_net', 'd');

                $chartDaily = [
                    'labels' => $days->map(fn($d) => str_pad((string)$d, 2, '0', STR_PAD_LEFT))->values(),
                    'series' => $days->map(fn($d) => (float)($rows[$d] ?? 0))->values(),
                ];
            } elseif ($period === 'last30') {
                $days = collect(range(29, 0))->map(fn($i) => now()->subDays($i)->startOfDay());

                $rows = CreditPayment::query()
                    ->notVoided()
                    ->whereBetween('created_at', [now()->subDays(29)->startOfDay(), now()->endOfDay()])
                    ->selectRaw("DATE(created_at) as d, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net")
                    ->groupBy('d')
                    ->pluck('total_net', 'd');

                $chartDaily = [
                    'labels' => $days->map(fn($d) => $d->format('d.m'))->values(),
                    'series' => $days->map(fn($d) => (float)($rows[$d->toDateString()] ?? 0))->values(),
                ];
            } elseif ($period === 'all') {
                $firstPaymentDate = CreditPayment::query()
                    ->notVoided()
                    ->min('created_at');

                if ($firstPaymentDate) {
                    $firstMonth = Carbon::parse($firstPaymentDate)->startOfMonth();
                    $lastMonth = now()->startOfMonth();

                    $months = collect();
                    $cursor = $firstMonth->copy();

                    while ($cursor <= $lastMonth) {
                        $months->push($cursor->copy());
                        $cursor->addMonth();
                    }

                    $rows = CreditPayment::query()
                        ->notVoided()
                        ->whereBetween('created_at', [$firstMonth->copy()->startOfDay(), now()->endOfDay()])
                        ->selectRaw("
                TO_CHAR(DATE_TRUNC('month', created_at), 'YYYY-MM') as month_key,
                COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net
            ")
                        ->groupBy('month_key')
                        ->orderBy('month_key')
                        ->pluck('total_net', 'month_key');

                    $chartDaily = [
                        'labels' => $months->map(fn($m) => $m->format('m.Y'))->values()->all(),
                        'series' => $months->map(fn($m) => (float) ($rows[$m->format('Y-m')] ?? 0))->values()->all(),
                    ];
                }
            }

            return compact('kpi', 'byCashier', 'topCashier', 'byBranch', 'recentAdminPayments', 'chartDaily');
        });

        return $data;
    }
}
