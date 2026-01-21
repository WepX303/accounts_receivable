<?php

namespace App\Http\Controllers\Payments;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CustomersPaymentController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('pages.payments.index');
    }
}
