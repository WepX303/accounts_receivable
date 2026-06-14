<?php

namespace App\Http\Controllers\Reports;

use App\Exports\RecoveryEffectivenessExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RecoveryEffectivenessExportController extends Controller
{
    public function __invoke(Request $request)
    {
        return Excel::download(
            new RecoveryEffectivenessExport($request),
            'recovery-effectiveness-' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}