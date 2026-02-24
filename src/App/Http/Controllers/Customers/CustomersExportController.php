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
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        return Excel::download(
            new CustomersExport($request),
            'customers.xlsx'
        );
    }
}
