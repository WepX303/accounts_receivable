<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Services\Customers\CustomerListService;
use Illuminate\Http\Request;

class CustomersController extends Controller
{
    public function __invoke(Request $request, CustomerListService $customers)
    {
        $request->validate(CustomerListService::rules(), CustomerListService::messages());

        $data = $customers->list($request->only(['q', 'quick_filter', 'date_from', 'date_to']));

        $data['credit_users']->appends($request->query());

        return view('pages.customers.index', $data);
    }
}
