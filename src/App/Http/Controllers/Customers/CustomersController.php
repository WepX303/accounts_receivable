<?php

namespace App\Http\Controllers\Customers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Credit;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;



// class CustomersController extends Controller
// {
//     public function __invoke(Request $request)
//     {
//         $q = trim((string) $request->get('q', ''));


//         $credit_users = Credit::query()
//             ->when($q !== '', function ($query) use ($q) {
//                 $like = '%' . $q . '%';
//                 $query->where(function ($qq) use ($like) {
//                     $qq->where('name', 'ilike', $like)
//                         ->orWhere('phone', 'ilike', $like)
//                         ->orWhere('passport', 'ilike', $like)
//                         ->orWhere('contract', 'ilike', $like)
//                         ->orWhere('clientref', 'ilike', $like);
//                 });
//             })
//             ->orderByDesc('rv_bigint')
//             ->paginate(25)
//             ->appends($request->query());


//         return view('pages.customers.index', compact('credit_users'));
//     }


// }



// class CustomersController extends Controller
// {
//     public function __invoke(Request $request)
//     {

//         $q = trim((string) ($request->get('q') ?? ''));
//         if ($q === 'null') $q = '';

//         $pay_range = (string) $request->get('pay_range', 'all');

//         [$from, $to] = match ($pay_range) {
//             'today'     => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
//             'yesterday' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
//             '7d'        => [Carbon::now()->subDays(7), Carbon::now()],
//             '14d'       => [Carbon::now()->subDays(14), Carbon::now()],
//             '1m'        => [Carbon::now()->subMonth(), Carbon::now()],
//             '3m'        => [Carbon::now()->subMonths(3), Carbon::now()],
//             '6m'        => [Carbon::now()->subMonths(6), Carbon::now()],
//             default     => [null, null],
//         };

//         $credit_users = Credit::query()
//             ->when($q !== '', function ($query) use ($q) {
//                 $like = '%' . $q . '%';
//                 $query->where(function ($qq) use ($like) {
//                     $qq->where('name', 'ilike', $like)
//                         ->orWhere('phone', 'ilike', $like)
//                         ->orWhere('passport', 'ilike', $like)
//                         ->orWhere('contract', 'ilike', $like)
//                         ->orWhere('clientref', 'ilike', $like);
//                 });
//             })
//             // Ödeme yapanlar filtresi (credit_payments.created_at'e göre)
//             ->when($from && $to, function ($query) use ($from, $to) {
//                 $table = $query->getModel()->getTable(); // credits_test gibi

//                 $query->whereExists(function ($sub) use ($from, $to, $table) {
//                     $sub->selectRaw('1')
//                         ->from('credit_payments')
//                         ->whereColumn('credit_payments.credit_logicalref', $table . '.logicalref')
//                         ->whereBetween('credit_payments.created_at', [$from, $to]);
//                 });
//             })

//             ->orderByDesc('rv_bigint')
//             ->paginate(25)
//             ->appends($request->query());

//         return view('pages.customers.index', compact('credit_users', 'pay_range'));
//     }
// }



class CustomersController extends Controller
{
    public function __invoke(Request $request)
    {
        // Search
        $q = trim((string) ($request->get('q') ?? ''));
        if ($q === 'null') $q = '';

        // Tek Quick Filter
        $quick = (string) $request->get('quick_filter', 'all');

        // Tarih aralıkları (ödeme bazlı)
        [$from, $to] = match ($quick) {
            'paid_today'     => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'paid_yesterday' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            'paid_7d'        => [Carbon::now()->subDays(7), Carbon::now()],
            'paid_14d'       => [Carbon::now()->subDays(14), Carbon::now()],
            'paid_1m'        => [Carbon::now()->subMonth(), Carbon::now()],
            'paid_3m'        => [Carbon::now()->subMonths(3), Carbon::now()],
            'paid_6m'        => [Carbon::now()->subMonths(6), Carbon::now()],
            default          => [null, null],
        };

        $credit_users = Credit::query()

            /* SEARCH */
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . $q . '%';
                $query->where(function ($qq) use ($like) {
                    $qq->where('name', 'ilike', $like)
                        ->orWhere('phone', 'ilike', $like)
                        ->orWhere('passport', 'ilike', $like)
                        ->orWhere('contract', 'ilike', $like)
                        ->orWhere('clientref', 'ilike', $like);
                });
            })

            /* ÖDEME TARİHİNE GÖRE */
            ->when($from && $to, function ($query) use ($from, $to) {
                $table = $query->getModel()->getTable();

                $query->whereExists(function ($sub) use ($from, $to, $table) {
                    $sub->selectRaw('1')
                        ->from('credit_payments')
                        ->whereColumn('credit_payments.credit_logicalref', $table . '.logicalref')
                        ->whereBetween('credit_payments.created_at', [$from, $to]);
                });
            })

            /* BORCU KALANLAR */
            ->when($quick === 'has_debt', function ($query) {
                $query->whereRaw('COALESCE(amount_local, amount) > COALESCE(paid_local, paid)');
            })

            /* BORCU OLMAYANLAR */
            ->when($quick === 'no_debt', function ($query) {
                $query->whereRaw('COALESCE(amount_local, amount) <= COALESCE(paid_local, paid)');
            })

            /* HİÇ ÖDEME YAPMAYANLAR */
            ->when($quick === 'no_payment', function ($query) {
                $table = $query->getModel()->getTable();

                $query->whereNotExists(function ($sub) use ($table) {
                    $sub->selectRaw('1')
                        ->from('credit_payments')
                        ->whereColumn('credit_payments.credit_logicalref', $table . '.logicalref');
                });
            })

            /* BLOK OLANLAR */
            ->when($quick === 'blocked', fn ($q) => $q->where('active', false))

            /* STATUS = BERMEJEK */
            ->when($quick === 'will_not_pay', fn ($q) => $q->where('status', 'Will Not Pay'))

            ->orderByDesc('rv_bigint')
            ->paginate(25)
            ->appends($request->query());

        return view('pages.customers.index', compact('credit_users', 'quick'));
    }
}
