<?php

namespace App\Http\Controllers\Customers;

use App\Exports\CustomersExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CustomersExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'quick_filter' => 'nullable|in:all,paid_today,paid_yesterday,paid_7d,paid_14d,paid_1m,paid_3m,paid_6m,paid_9m,paid_12m,has_debt,no_debt,no_payment,blocked,active,bermejek,paid_mismatch',
        ], [
            'q.string' => __('validations/validations.customers.q_string'),
            'q.max' => __('validations/validations.customers.q_max'),
            'quick_filter.in' => __('validations/validations.customers.quick_invalid'),
        ]);

        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        return Excel::download(
            new CustomersExport($request),
            'customers.xlsx'
        );
    }
}
