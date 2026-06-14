<?php

namespace App\Http\Controllers\Reports;

use App\Exports\PaymentCalendarDetailsExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PaymentCalendarDetailsExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'type' => 'required|in:expected,received',
            'date' => 'required|date',
        ]);

        $type = (string) $request->get('type');
        $date = Carbon::parse($request->get('date'))->format('Y-m-d');

        return Excel::download(
            new PaymentCalendarDetailsExport($type, $date),
            'payment-calendar-' . $type . '-' . $date . '.xlsx'
        );
    }
}