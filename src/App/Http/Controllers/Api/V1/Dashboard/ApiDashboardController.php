<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\CreditPaymentResource;
use App\Models\CreditPayment;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ApiDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'period' => ['nullable', 'in:today,yesterday,week,month,last7,last30,custom,all'],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date', 'after_or_equal:start'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $role = $user->role;

        $period = (string) $request->input('period', 'today');

        $start = now()->startOfDay();
        $end = now()->endOfDay();

        switch ($period) {
            case 'today':
                $start = now()->startOfDay();
                $end = now()->endOfDay();
                break;

            case 'yesterday':
                $start = now()->subDay()->startOfDay();
                $end = now()->subDay()->endOfDay();
                break;

            case 'week':
                $start = now()->startOfWeek()->startOfDay();
                $end = now()->endOfDay();
                break;

            case 'month':
                $start = now()->startOfMonth()->startOfDay();
                $end = now()->endOfDay();
                break;

            case 'last7':
                $start = now()->subDays(6)->startOfDay();
                $end = now()->endOfDay();
                break;

            case 'last30':
                $start = now()->subDays(29)->startOfDay();
                $end = now()->endOfDay();
                break;

            case 'custom':
                if ($request->filled('start')) {
                    $start = Carbon::parse($request->input('start'))->startOfDay();
                }
                if ($request->filled('end')) {
                    $end = Carbon::parse($request->input('end'))->endOfDay();
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

            default:
                $period = 'today';
                $start = now()->startOfDay();
                $end = now()->endOfDay();
                break;
        }

        if ($role === UserRoleEnum::CASHIER) {
            return $this->cashierDashboard($user->id, $start, $end, $period);
        }

        return $this->adminDashboard($start, $end, $period);
    }

    private function cashierDashboard(int $userId, Carbon $start, Carbon $end, string $period)
    {
        $cacheKey = 'api_dashboard_cashier:' . md5(json_encode([
            'user_id' => $userId,
            'period' => $period,
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
            'v' => Cache::get('admin_dashboard:v', 1),
        ]));

        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userId, $start, $end, $period) {
            $base = CreditPayment::query()
                ->notVoided()
                ->forCashier($userId);

            $statsRow = (clone $base)
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount, 0)), 0) as total_net')
                ->selectRaw('COUNT(*) as total_count')
                ->selectRaw('COALESCE(AVG(pay_amount - COALESCE(change_amount, 0)), 0) as avg_payment')
                ->selectRaw('COALESCE(SUM(cash_amount), 0) as total_cash')
                ->selectRaw('COALESCE(SUM(card_amount), 0) as total_card')
                ->selectRaw('COALESCE(SUM(phone_amount), 0) as total_phone')
                ->selectRaw('MAX(created_at) as last_payment_at')
                ->first();

            $recentPayments = (clone $base)
                ->whereBetween('created_at', [$start, $end])
                ->orderByDesc('id')
                ->limit(10)
                ->get();

            return [
                'type' => 'cashier',
                'period' => [
                    'key' => $period,
                    'start' => $start->format('Y-m-d H:i:s'),
                    'end' => $end->format('Y-m-d H:i:s'),
                ],
                'stats' => [
                    'total_net' => (float) ($statsRow->total_net ?? 0),
                    'total_count' => (int) ($statsRow->total_count ?? 0),
                    'avg_payment' => (float) ($statsRow->avg_payment ?? 0),
                    'total_cash' => (float) ($statsRow->total_cash ?? 0),
                    'total_card' => (float) ($statsRow->total_card ?? 0),
                    'total_phone' => (float) ($statsRow->total_phone ?? 0),
                    'last_payment_at' => $statsRow->last_payment_at
                        ? Carbon::parse($statsRow->last_payment_at)->format('Y-m-d H:i:s')
                        : null,
                ],
                'recent_payments' => CreditPaymentResource::collection($recentPayments)->resolve(),
                'chart' => $this->buildChart($start, $end, $period, $userId),
            ];
        });

        return ApiResponse::success($data, 'Dashboard fetched successfully.');
    }

    private function adminDashboard(Carbon $start, Carbon $end, string $period)
    {
        $dashV = (int) Cache::get('admin_dashboard:v', 1);

        $cacheKey = 'api_dashboard_admin:' . md5(json_encode([
            'v' => $dashV,
            'period' => $period,
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
        ]));

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($start, $end, $period) {
            $base = CreditPayment::query()
                ->notVoided()
                ->whereBetween('created_at', [$start, $end]);

            $row = (clone $base)
                ->selectRaw('COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net')
                ->selectRaw('COUNT(*) as total_count')
                ->selectRaw('COALESCE(AVG(pay_amount - COALESCE(change_amount,0)),0) as avg_net')
                ->selectRaw('COALESCE(SUM(cash_amount),0) as total_cash')
                ->selectRaw('COALESCE(SUM(card_amount),0) as total_card')
                ->selectRaw('COALESCE(SUM(phone_amount),0) as total_phone')
                ->selectRaw('COUNT(CASE WHEN method = \'mixed\' THEN 1 END) as mixed_tx_count')
                ->selectRaw('MAX(created_at) as last_payment_at')
                ->first();

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
                ->get()
                ->map(function ($item) {
                    return [
                        'cashier_id' => $item->cashier_id ? (int) $item->cashier_id : null,
                        'cashier_name' => $item->cashier_name,
                        'tx_count' => (int) $item->tx_count,
                        'total_net' => (float) $item->total_net,
                        'total_cash' => (float) $item->total_cash,
                        'total_card' => (float) $item->total_card,
                        'total_phone' => (float) $item->total_phone,
                    ];
                })
                ->values();

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
                ->get()
                ->map(function ($item) {
                    return [
                        'branch' => $item->branch,
                        'tx_count' => (int) $item->tx_count,
                        'total_net' => (float) $item->total_net,
                        'total_cash' => (float) $item->total_cash,
                        'total_card' => (float) $item->total_card,
                        'total_phone' => (float) $item->total_phone,
                    ];
                })
                ->values();

            $recentPayments = (clone $base)
                ->with([
                    'correctedByUser:id,firstname,lastname',
                    'voidedByUser:id,firstname,lastname',
                ])
                ->orderByDesc('id')
                ->limit(20)
                ->get();

            return [
                'type' => 'admin',
                'period' => [
                    'key' => $period,
                    'start' => $start->format('Y-m-d H:i:s'),
                    'end' => $end->format('Y-m-d H:i:s'),
                ],
                'stats' => [
                    'total_net' => (float) ($row->total_net ?? 0),
                    'total_count' => (int) ($row->total_count ?? 0),
                    'avg_net' => (float) ($row->avg_net ?? 0),
                    'total_cash' => (float) ($row->total_cash ?? 0),
                    'total_card' => (float) ($row->total_card ?? 0),
                    'total_phone' => (float) ($row->total_phone ?? 0),
                    'all_total' => (float) (
                        ($row->total_cash ?? 0) +
                        ($row->total_card ?? 0) +
                        ($row->total_phone ?? 0)
                    ),
                    'mixed_tx_count' => (int) ($row->mixed_tx_count ?? 0),
                    'last_payment_at' => $row->last_payment_at
                        ? Carbon::parse($row->last_payment_at)->format('Y-m-d H:i:s')
                        : null,
                ],
                'top_cashier' => $byCashier->first(),
                'by_cashier' => $byCashier,
                'by_branch' => $byBranch,
                'recent_payments' => CreditPaymentResource::collection($recentPayments)->resolve(),
                'chart' => $this->buildChart($start, $end, $period),
            ];
        });

        return ApiResponse::success($data, 'Dashboard fetched successfully.');
    }

    private function buildChart(Carbon $start, Carbon $end, string $period, ?int $cashierId = null): array
    {
        $query = CreditPayment::query()
            ->notVoided()
            ->whereBetween('created_at', [$start, $end]);

        if ($cashierId !== null) {
            $query->forCashier($cashierId);
        }

        if (in_array($period, ['today', 'yesterday'], true)) {
            $hours = collect(range(0, 23));

            $rows = (clone $query)
                ->selectRaw('EXTRACT(HOUR FROM created_at) as h, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net')
                ->groupBy('h')
                ->pluck('total_net', 'h');

            return [
                'labels' => $hours->map(fn($h) => str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00')->values()->all(),
                'series' => $hours->map(fn($h) => (float) ($rows[$h] ?? 0))->values()->all(),
            ];
        }

        if ($period === 'week') {
            $days = collect();
            $cursor = $start->copy();

            while ($cursor <= $end) {
                $days->push($cursor->copy());
                $cursor->addDay();
            }

            $rows = (clone $query)
                ->selectRaw('DATE(created_at) as d, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net')
                ->groupBy('d')
                ->pluck('total_net', 'd');

            return [
                'labels' => $days->map(fn($d) => $d->format('d.m'))->values()->all(),
                'series' => $days->map(fn($d) => (float) ($rows[$d->toDateString()] ?? 0))->values()->all(),
            ];
        }

        if ($period === 'last7') {
            $days = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->startOfDay());

            $rows = (clone $query)
                ->selectRaw('DATE(created_at) as d, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net')
                ->groupBy('d')
                ->pluck('total_net', 'd');

            return [
                'labels' => $days->map(fn($d) => $d->format('d.m'))->values()->all(),
                'series' => $days->map(fn($d) => (float) ($rows[$d->toDateString()] ?? 0))->values()->all(),
            ];
        }

        if ($period === 'month') {
            $daysInMonth = $end->day;
            $days = collect(range(1, $daysInMonth));

            $rows = (clone $query)
                ->selectRaw('EXTRACT(DAY FROM created_at) as d, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net')
                ->groupBy('d')
                ->pluck('total_net', 'd');

            return [
                'labels' => $days->map(fn($d) => str_pad((string) $d, 2, '0', STR_PAD_LEFT))->values()->all(),
                'series' => $days->map(fn($d) => (float) ($rows[$d] ?? 0))->values()->all(),
            ];
        }

        if ($period === 'last30' || $period === 'custom') {
            $days = collect();
            $cursor = $start->copy()->startOfDay();

            while ($cursor <= $end) {
                $days->push($cursor->copy());
                $cursor->addDay();
            }

            $rows = (clone $query)
                ->selectRaw('DATE(created_at) as d, COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net')
                ->groupBy('d')
                ->pluck('total_net', 'd');

            return [
                'labels' => $days->map(fn($d) => $d->format('d.m'))->values()->all(),
                'series' => $days->map(fn($d) => (float) ($rows[$d->toDateString()] ?? 0))->values()->all(),
            ];
        }

        if ($period === 'all') {
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

                $monthQuery = CreditPayment::query()
                    ->notVoided()
                    ->whereBetween('created_at', [$firstMonth->copy()->startOfDay(), now()->endOfDay()]);

                if ($cashierId !== null) {
                    $monthQuery->forCashier($cashierId);
                }

                $rows = $monthQuery
                    ->selectRaw("
                        TO_CHAR(DATE_TRUNC('month', created_at), 'YYYY-MM') as month_key,
                        COALESCE(SUM(pay_amount - COALESCE(change_amount,0)),0) as total_net
                    ")
                    ->groupBy('month_key')
                    ->orderBy('month_key')
                    ->pluck('total_net', 'month_key');

                return [
                    'labels' => $months->map(fn($m) => $m->format('m.Y'))->values()->all(),
                    'series' => $months->map(fn($m) => (float) ($rows[$m->format('Y-m')] ?? 0))->values()->all(),
                ];
            }
        }

        return [
            'labels' => [],
            'series' => [],
        ];
    }
}