<?php

namespace App\Http\Controllers\Reports;

use App\Exports\DailyCashClosingExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DailyCashClosingExportController extends Controller
{
    public function __invoke(Request $request)
    {
        return Excel::download(
            new DailyCashClosingExport($request),
            'daily-cash-closing-' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}