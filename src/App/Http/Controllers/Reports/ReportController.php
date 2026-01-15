<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;

class ReportController extends Controller
{
    public function index() {
        return view('pages.reports.apps-calendar-month-grid');
    }

    public function order() {
        return view('pages.reports.apps-ecommerce-orders');
    }

    public function details() {
        return view('pages.reports.apps-ecommerce-order-details');
    }
}
