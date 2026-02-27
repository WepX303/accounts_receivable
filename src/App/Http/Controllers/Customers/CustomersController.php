<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\CreditPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomersController extends Controller
{
    public function __invoke(Request $request)
    {
        // Search
        $q = trim((string) ($request->get('q') ?? ''));
        if ($q === 'null') {
            $q = '';
        }

        // Tek Quick Filter
        $quick = (string) $request->get('quick_filter', 'all');

        // Tarih aralıkları (ödeme bazlı)
        [$from, $to] = match ($quick) {
            'paid_today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'paid_yesterday' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            'paid_7d' => [Carbon::now()->subDays(7), Carbon::now()],
            'paid_14d' => [Carbon::now()->subDays(14), Carbon::now()],
            'paid_1m' => [Carbon::now()->subMonth(), Carbon::now()],
            'paid_3m' => [Carbon::now()->subMonths(3), Carbon::now()],
            'paid_6m' => [Carbon::now()->subMonths(6), Carbon::now()],
            'paid_9m' => [Carbon::now()->subMonths(9), Carbon::now()],
            'paid_12m' => [Carbon::now()->subMonths(12), Carbon::now()],
            default => [null, null],
        };

        // Tablodaki "Today Paid" kolonu için dinamik tarih aralığı
        // Eğer ödeme bazlı filtre seçiliyse ($from/$to dolu) o aralığı kullan,
        // değilse varsayılan olarak bugünü kullan.
        [$periodFrom, $periodTo] = ($from && $to)
            ? [$from->copy()->startOfDay(), $to->copy()->endOfDay()]
            : [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];

        $periodLabel = match ($quick) {
            'paid_today' => __('pages/customers_index.today_paid'),
            'paid_yesterday' => __('pages/customers_index.paid_yesterday'),
            'paid_7d' => __('pages/customers_index.paid_last_7_days'),
            'paid_14d' => __('pages/customers_index.paid_last_14_days'),
            'paid_1m' => __('pages/customers_index.paid_last_1_month'),
            'paid_3m' => __('pages/customers_index.paid_last_3_months'),
            'paid_6m' => __('pages/customers_index.paid_last_6_months'),
            'paid_9m' => __('pages/customers_index.paid_last_9_months'),
            'paid_12m' => __('pages/customers_index.paid_last_12_months'),
            default => __('pages/customers_index.today_paid'),
        };

        /**
         * =========================
         *  DASHBOARD CARDS (STATS)
         * =========================
         */
        $todayFrom = Carbon::today()->startOfDay();
        $todayTo = Carbon::today()->endOfDay();

        $yFrom = Carbon::yesterday()->startOfDay();
        $yTo = Carbon::yesterday()->endOfDay();

        $stats = [
            // bugün tahsilat
            'paid_today_sum' => (float) CreditPayment::query()
                ->notVoided()
                ->whereBetween('created_at', [$todayFrom, $todayTo])
                ->sum(DB::raw('pay_amount - COALESCE(change_amount,0)')),

            'paid_today_cnt' => (int) CreditPayment::query()
                ->notVoided()
                ->whereBetween('created_at', [$todayFrom, $todayTo])
                ->count(),

            'paid_y_sum' => (float) CreditPayment::query()
                ->notVoided()
                ->whereBetween('created_at', [$yFrom, $yTo])
                ->sum(DB::raw('pay_amount - COALESCE(change_amount,0)')),

            'paid_y_cnt' => (int) CreditPayment::query()
                ->notVoided()
                ->whereBetween('created_at', [$yFrom, $yTo])
                ->count(),

            // borçlu müşteri sayısı (MERKEZ amount/paid)
            'has_debt_cnt' => (int) Credit::query()
                ->whereRaw('COALESCE(amount, 0) > COALESCE(paid, 0)')
                ->count(),

            // local != remote paid olan satırlar (tabloda kırmızıya boyadıkların)
            'paid_mismatch_cnt' => (int) Credit::query()
                ->whereNotNull('paid_local')
                ->whereRaw('ROUND(COALESCE(paid_local,0)::numeric, 2) <> ROUND(COALESCE(paid,0)::numeric, 2)')
                ->count(),

        ];

        /**
         * =========================
         *  MAIN LIST QUERY
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

            /* ÖDEME TARİHİNE GÖRE (credit_payments üzerinden) */
            ->when($from && $to, function ($query) use ($from, $to) {
                $table = $query->getModel()->getTable();

                $query->whereExists(function ($sub) use ($from, $to, $table) {
                    $sub->selectRaw('1')
                        ->from('credit_payments')
                        ->whereColumn('credit_payments.credit_logicalref', $table . '.logicalref')
                        // void olanlari alma
                        ->whereNull('credit_payments.voided_at')

                        ->whereBetween('credit_payments.created_at', [$from, $to]);
                });
            })

            /* BORCU KALANLAR (MERKEZ: amount/paid) */
            ->when($quick === 'has_debt', function ($query) {
                $query->whereRaw('COALESCE(amount, 0) > COALESCE(paid, 0)');
            })

            /* BORCU OLMAYANLAR (MERKEZ: amount/paid) */
            ->when($quick === 'no_debt', function ($query) {
                $query->whereRaw('COALESCE(amount, 0) <= COALESCE(paid, 0)');
            })

            /* HİÇ ÖDEME YAPMAYANLAR */
            ->when($quick === 'no_payment', function ($query) {
                $table = $query->getModel()->getTable();

                $query->whereNotExists(function ($sub) use ($table) {
                    $sub->selectRaw('1')
                        ->from('credit_payments')
                        ->whereColumn('credit_payments.credit_logicalref', $table . '.logicalref')
                        ->whereNull('credit_payments.voided_at');
                });
            })

            /* BLOK OLANLAR */
            ->when($quick === 'blocked', fn($q) => $q->where('active', false))

            /* AKTİF OLANLAR */
            ->when($quick === 'active', fn($q) => $q->where('active', true))

            /* STATUS = BERMEJEK */
            ->when($quick === 'bermejek', function ($q) {
                $q->whereNotNull('status')
                    ->where('status', '!=', '')
                    ->whereRaw('LOWER(TRIM(status)) = ?', ['bermejek']);
            })

            /* PAID MISMATCH (LOCAL != REMOTE) */
            ->when($quick === 'paid_mismatch', function ($query) {
                $query->whereNotNull('paid_local')
                    ->whereRaw('ROUND(COALESCE(paid_local,0)::numeric, 2) <> ROUND(COALESCE(paid,0)::numeric, 2)');
            })

            //void without

            ->withSum(['payments as period_pay_sum' => function ($q) use ($periodFrom, $periodTo) {
                $q->whereNull('voided_at')
                    ->whereBetween('created_at', [$periodFrom, $periodTo]);
            }], 'pay_amount')

            ->withSum(['payments as period_change_sum' => function ($q) use ($periodFrom, $periodTo) {
                $q->whereNull('voided_at')
                    ->whereBetween('created_at', [$periodFrom, $periodTo]);
            }], 'change_amount')

            ->orderByDesc('rv_bigint')
            ->paginate(25)
            ->appends($request->query());

        return view('pages.customers.index', compact('credit_users', 'quick', 'stats', 'periodLabel'));
    }
}
