<?php

namespace App\Http\Controllers\Reports;

use App\Exports\PromiseToPayExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PromiseToPayExportController extends Controller
{
    public function __invoke(Request $request)
    {
        return Excel::download(
            new PromiseToPayExport($request),
            'promise-to-pay-' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}