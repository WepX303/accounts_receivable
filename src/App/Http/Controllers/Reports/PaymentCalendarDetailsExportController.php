<?php

namespace App\Http\Controllers\Reports;

use App\Exports\PaymentCalendarDetailsExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\PaymentCalendarReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PaymentCalendarDetailsExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'type' => 'required|in:expected,expected-paid,received',
            'date' => 'required|date',
            'branch' => 'nullable|array',
            'branch.*' => 'nullable|string|max:100',
        ]);

        $type = (string) $request->get('type');
        $date = Carbon::parse($request->get('date'))->format('Y-m-d');
        $branches = PaymentCalendarReportService::normalizeBranches($request->input('branch', []));

        $suffix = $branches === [] ? 'all' : implode('-', $branches);

        return Excel::download(
            new PaymentCalendarDetailsExport($type, $date, $branches),
            'payment-calendar-' . $type . '-' . $date . '-' . $suffix . '.xlsx'
        );
    }
}
