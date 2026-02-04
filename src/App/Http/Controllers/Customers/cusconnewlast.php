<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class cusconnewlast extends Controller
{
    public function __invoke(Request $request)
    {
        /**
         * =========================
         *  1) INPUTS
         * =========================
         */
        $q = trim((string) ($request->get('q') ?? ''));
        if ($q === 'null') {
            $q = '';
        }

        // tek quick filter (dropdown)
        $quick = (string) $request->get('quick_filter', 'all');

        /**
         * =========================
         *  2) DATE RANGES (PAYMENTS)
         * =========================
         */
        [$from, $to] = match ($quick) {
            'paid_today'     => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'paid_yesterday' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            'paid_7d'        => [Carbon::now()->subDays(7), Carbon::now()],
            'paid_14d'       => [Carbon::now()->subDays(14), Carbon::now()],
            'paid_1m'        => [Carbon::now()->subMonth(), Carbon::now()],
            'paid_3m'        => [Carbon::now()->subMonths(3), Carbon::now()],
            'paid_6m'        => [Carbon::now()->subMonths(6), Carbon::now()],
            'paid_9m'        => [Carbon::now()->subMonths(9), Carbon::now()],
            'paid_12m'       => [Carbon::now()->subMonths(12), Carbon::now()],
            default          => [null, null],
        };

        /**
         * =========================
         *  3) STATUS LIST (for dropdown)
         *  - exclude NULL/empty
         *  - normalize to UPPER+TRIM
         * =========================
         */
        $statusList = Credit::query()
            ->selectRaw("UPPER(TRIM(status)) as status_norm")
            ->whereNotNull('status')
            ->whereRaw("TRIM(status) <> ''")
            ->distinct()
            ->orderBy('status_norm')
            ->pluck('status_norm')
            ->values();

        // Parse "status:XXXX" (optional)
        $statusSelected = null;
        if (str_starts_with($quick, 'status:')) {
            $statusSelected = strtoupper(trim(substr($quick, 7)));
            if ($statusSelected === '') {
                $statusSelected = null;
            }
        }

        /**
         * =========================
         *  4) DASHBOARD CARDS (STATS)
         * =========================
         */
        $todayFrom = Carbon::today()->startOfDay();
        $todayTo   = Carbon::today()->endOfDay();

        $yFrom = Carbon::yesterday()->startOfDay();
        $yTo   = Carbon::yesterday()->endOfDay();

        $stats = [
            // today payments
            'paid_today_sum' => (float) CreditPayment::query()
                ->whereBetween('created_at', [$todayFrom, $todayTo])
                ->sum('pay_amount'),
            'paid_today_cnt' => (int) CreditPayment::query()
                ->whereBetween('created_at', [$todayFrom, $todayTo])
                ->count(),

            // yesterday payments
            'paid_y_sum' => (float) CreditPayment::query()
                ->whereBetween('created_at', [$yFrom, $yTo])
                ->sum('pay_amount'),
            'paid_y_cnt' => (int) CreditPayment::query()
                ->whereBetween('created_at', [$yFrom, $yTo])
                ->count(),

            // customers with debt (LOCAL first, fallback to REMOTE)
            'has_debt_cnt' => (int) Credit::query()
                ->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)')
                ->count(),

            // paid mismatch (LOCAL != REMOTE), numeric rounding to 2 decimals
            'paid_mismatch_cnt' => (int) Credit::query()
                ->whereNotNull('paid_local')
                ->whereRaw('ROUND(COALESCE(paid_local,0)::numeric, 2) <> ROUND(COALESCE(paid,0)::numeric, 2)')
                ->count(),
        ];

        /**
         * =========================
         *  5) MAIN LIST QUERY
         * =========================
         */
        $credit_users = Credit::query()

            /* SEARCH */
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(function ($qq) use ($like) {
                    $qq->where('name', 'ilike', $like)
                        ->orWhere('phone', 'ilike', $like)
                        ->orWhere('passport', 'ilike', $like)
                        ->orWhere('contract', 'ilike', $like)
                        ->orWhere('clientref', 'ilike', $like)
                        ->orWhere('assurance', 'ilike', $like);
                });
            })

            /* PAYMENTS DATE RANGE (credit_payments) */
            ->when($from && $to, function ($query) use ($from, $to) {
                $table = $query->getModel()->getTable();

                $query->whereExists(function ($sub) use ($from, $to, $table) {
                    $sub->selectRaw('1')
                        ->from('credit_payments')
                        ->whereColumn('credit_payments.credit_logicalref', $table . '.logicalref')
                        ->whereBetween('credit_payments.created_at', [$from, $to]);
                });
            })

            /* HAS DEBT (LOCAL first, fallback to REMOTE) */
            ->when($quick === 'has_debt', function ($query) {
                $query->whereRaw('COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)');
            })

            /* NO DEBT (LOCAL first, fallback to REMOTE) */
            ->when($quick === 'no_debt', function ($query) {
                $query->whereRaw('COALESCE(amount_local, amount, 0) <= COALESCE(paid_local, paid, 0)');
            })

            /* NO PAYMENT EVER */
            ->when($quick === 'no_payment', function ($query) {
                $table = $query->getModel()->getTable();

                $query->whereNotExists(function ($sub) use ($table) {
                    $sub->selectRaw('1')
                        ->from('credit_payments')
                        ->whereColumn('credit_payments.credit_logicalref', $table . '.logicalref');
                });
            })

            /* BLOCKED */
            ->when($quick === 'blocked', fn($q) => $q->where('active', false))

            /* ACTIVE */
            ->when($quick === 'active', fn($q) => $q->where('active', true))

            /* BERMEJEK (normalize: UPPER+TRIM) */
            ->when(
                $quick === 'bermejek',
                fn($q) =>
                $q->whereRaw("UPPER(TRIM(COALESCE(status,''))) = ?", ['BERMEJEK'])
            )

            /* PAID MISMATCH (LOCAL != REMOTE) */
            ->when($quick === 'paid_mismatch', function ($query) {
                $query->whereNotNull('paid_local')
                    ->whereRaw('ROUND(COALESCE(paid_local,0)::numeric, 2) <> ROUND(COALESCE(paid,0)::numeric, 2)');
            })

            /* ✅ DYNAMIC STATUS FILTER (status:XXXX or status:EMPTY) */
            ->when($statusSelected === 'EMPTY', function ($query) {
                $query->where(function ($qq) {
                    $qq->whereNull('status')
                        ->orWhereRaw("TRIM(COALESCE(status,'')) = ''");
                });
            })
            ->when($statusSelected && $statusSelected !== 'EMPTY', function ($query) use ($statusSelected) {
                $query->whereRaw("UPPER(TRIM(COALESCE(status,''))) = ?", [$statusSelected]);
            })

            ->orderByDesc('rv_bigint')
            ->paginate(25)
            ->appends($request->query());

        return view('pages.customers.index', compact('credit_users', 'quick', 'stats', 'statusList'));
    }
}
