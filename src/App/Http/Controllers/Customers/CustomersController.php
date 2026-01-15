<?php

namespace app\Http\Controllers\Customers;

use Illuminate\Http\Request;
use app\Http\Controllers\Controller;

class CustomersController extends Controller
{
    public function __invoke()
    {
        return view('pages.customers.index');
    }
}
