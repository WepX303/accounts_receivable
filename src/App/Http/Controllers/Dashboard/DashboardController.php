<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard)
    {
        $user = auth()->user();

        // =========================
        // CASHIER (cashier.blade.php)
        // =========================
        if ($user->role === UserRoleEnum::CASHIER) {
            return view('pages.dashboard.cashier', $dashboard->cashier($user));
        }

        // =========================
        // ADMIN (dashboard.blade.php)
        // =========================

        $request->validate(DashboardService::rules(), DashboardService::messages());

        [$period, $start, $end] = $dashboard->period($request->get('period', 'today'), $request->start, $request->end);

        return view('pages.dashboard.dashboard', [
            ...$dashboard->overview($user, $period, $start, $end),
            'period' => $period,
            'start' => $start,
            'end' => $end,
        ]);
    }
}
