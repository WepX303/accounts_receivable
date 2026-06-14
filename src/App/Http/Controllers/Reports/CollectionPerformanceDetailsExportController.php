<?php

namespace App\Http\Controllers\Reports;

use App\Exports\CollectionPerformanceDetailsExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CollectionPerformanceDetailsExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'type' => 'required|in:cashier,branch',
            'value' => 'required|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'method' => 'nullable|in:all,cash,card,phone',
        ]);

        return Excel::download(
            new CollectionPerformanceDetailsExport($request),
            'collection-performance-details-' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}