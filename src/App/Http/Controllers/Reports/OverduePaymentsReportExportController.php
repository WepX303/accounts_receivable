<?php

namespace App\Http\Controllers\Reports;

use App\Exports\OverduePaymentsReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OverduePaymentsReportExportController extends Controller
{
    public function __invoke(Request $request)
    {
        return Excel::download(
            new OverduePaymentsReportExport($request),
            'overdue-payments-' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}