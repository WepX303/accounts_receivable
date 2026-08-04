<?php

namespace App\Http\Controllers\Reports;

use App\Exports\BalanceIntegrityExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BalanceIntegrityExportController extends Controller
{
    public function __invoke(Request $request)
    {
        return Excel::download(
            new BalanceIntegrityExport($request),
            'balance-integrity-' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
