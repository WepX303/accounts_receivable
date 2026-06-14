<?php

namespace App\Http\Controllers\Reports;

use App\Exports\CollectionPerformanceExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CollectionPerformanceExportController extends Controller
{
    public function __invoke(Request $request)
    {
        return Excel::download(
            new CollectionPerformanceExport($request),
            'collection-performance-' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}