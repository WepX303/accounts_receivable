<?php

namespace App\Http\Controllers\Customers;

use App\Exports\CustomersExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\AuditLogger;
use App\Services\Customers\CustomerListService;

class CustomersExportController extends Controller
{
    public function __invoke(Request $request)
    {
        // The list's own rules: whatever the list accepts, its export accepts.
        $request->validate(CustomerListService::rules(), CustomerListService::messages());

        $audit = app(AuditLogger::class);

        $audit->log(
            action: 'customers_exported',
            category: 'export',
            subject: null,
            oldValues: null,
            newValues: null,
            extra: [
                'q' => $request->get('q'),
                'quick_filter' => $request->get('quick_filter'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ],
            message: 'Customers export downloaded',
            isSuccess: true,
            severity: 'info',
            isSuspicious: false
        );

        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        return Excel::download(
            new CustomersExport($request),
            'customers.xlsx'
        );
    }
}
