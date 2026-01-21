<?php

namespace App\Http\Controllers\Customers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Credit;
use Illuminate\Support\Facades\DB;


class CustomersController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $credit_users = Credit::query()
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
            ->orderByDesc('rv_bigint')
            ->paginate(25)
            ->appends($request->query());


        return view('pages.customers.index', compact('credit_users'));
    }



}
