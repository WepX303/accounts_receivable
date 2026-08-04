<?php

namespace App\Http\Controllers\Reports;

use App\Exports\VoidCorrectionExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class VoidCorrectionExportController extends Controller
{
    public function __invoke(Request $request)
    {
        return Excel::download(
            new VoidCorrectionExport($request),
            'void-correction-' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
