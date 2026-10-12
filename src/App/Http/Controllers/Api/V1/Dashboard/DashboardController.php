<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use App\Services\Payments\Money;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * The same split as the panel: a cashier gets their own day, everyone else the overview.
     */
    public function __invoke(Request $request, DashboardService $dashboard)
    {
        $user = $request->user();

        if ($user->role === UserRoleEnum::CASHIER) {
            $data = $dashboard->cashier($user);

            return ApiResponse::success([
                'type' => 'cashier',
                'stats' => [
                    'today_total' => Money::format($data['stats']['today_total']),
                    'today_count' => $data['stats']['today_count'],
                    'avg_payment' => Money::format($data['stats']['avg_payment']),
                    'last_payment_at' => $this->instant($data['stats']['last_payment_at']),
                ],
                'recent_payments' => $data['recentPayments']->map(fn ($p) => [
                    'amount' => Money::format($p->amount),
                    'paid_at' => $p->created_at?->toIso8601String(),
                    'customer_name' => $p->customer_name,
                    'customer_phone' => $p->customer_phone,
                ])->all(),
            ]);
        }

        $filters = $request->validate(DashboardService::rules(), DashboardService::messages());

        [$period, $start, $end] = $dashboard->period(
            $filters['period'] ?? 'today',
            $filters['start'] ?? null,
            $filters['end'] ?? null
        );

        $data = $dashboard->overview($user, $period, $start, $end);
        $kpi = $data['kpi'];

        $totals = fn ($row) => [
            'tx_count' => (int) $row->tx_count,
            'total_net' => Money::format((float) $row->total_net),
            'total_cash' => Money::format((float) $row->total_cash),
            'total_card' => Money::format((float) $row->total_card),
            'total_phone' => Money::format((float) $row->total_phone),
        ];

        $cashier = fn ($row) => ['cashier_id' => $row->cashier_id, 'cashier_name' => $row->cashier_name, ...$totals($row)];

        return ApiResponse::success([
            'type' => 'overview',
            'period' => $period,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'kpi' => [
                'total_net' => Money::format($kpi['total_net']),
                'total_count' => $kpi['total_count'],
                'avg_net' => Money::format($kpi['avg_net']),
                'total_cash' => Money::format($kpi['total_cash']),
                'total_card' => Money::format($kpi['total_card']),
                'total_phone' => Money::format($kpi['total_phone']),
                'all_total' => Money::format($kpi['all_total']),
                'mixed_tx_count' => $kpi['mixed_tx_count'],
                'last_payment_at' => $this->instant($kpi['last_payment_at']),
            ],
            'top_cashier' => $data['topCashier'] ? $cashier($data['topCashier']) : null,
            'by_cashier' => $data['byCashier']->map($cashier)->all(),
            'by_branch' => $data['byBranch']->map(fn ($row) => ['branch' => $row->branch, ...$totals($row)])->all(),
            'recent_payments' => $data['recentAdminPayments']->map(fn ($p) => [
                'id' => $p->id,
                'branch' => $p->branch,
                'cashier' => $p->created_by_name,
                'customer_name' => $p->customer_name,
                'contract' => $p->customer_contract,
                'method' => $p->method,
                'amount' => $p->pay_amount,
                'change' => $p->change_amount,
                'net_amount' => Money::format($p->net_amount),
                'cash_amount' => $p->cash_amount,
                'card_amount' => $p->card_amount,
                'phone_amount' => $p->phone_amount,
                'paid_at' => $p->created_at?->toIso8601String(),
                'corrected_at' => $p->corrected_at?->toIso8601String(),
                'corrected_by' => $p->correctedByUser?->full_name,
                'correct_reason' => $p->correct_reason,
            ])->all(),
            'chart' => [
                'labels' => collect($data['chartDaily']['labels'])->values()->all(),
                'series' => collect($data['chartDaily']['series'])->map(fn ($v) => Money::format((float) $v))->values()->all(),
            ],
        ]);
    }

    private function instant($value): ?string
    {
        return $value ? Carbon::parse($value)->toIso8601String() : null;
    }
}
