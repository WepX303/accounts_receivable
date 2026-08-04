<?php

namespace App\Http\Controllers\Reports;

use App\Exports\PaymentCalendarReportExport;
use App\Http\Controllers\Controller;
use App\Services\Reports\PaymentCalendarReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PaymentCalendarReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'month' => 'nullable|string|max:7',
            'branch' => 'nullable|array',
            'branch.*' => 'nullable|string|max:100',
        ]);

        $service = new PaymentCalendarReportService(
            $request->get('month'),
            (array) $request->input('branch', [])
        );

        $currentMonth = $service->month();

        return view('pages.reports.payment-calendar.index', [
            'days' => $service->days(),
            'summary' => $service->summary(),
            'currentMonth' => $currentMonth,
            'previousMonth' => $currentMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $currentMonth->copy()->addMonth()->format('Y-m'),
            'branches' => $service->availableBranches(),
            'selectedBranches' => $service->branches(),
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'month' => 'nullable|string|max:7',
            'branch' => 'nullable|array',
            'branch.*' => 'nullable|string|max:100',
        ]);

        $month = $request->get('month') ?: now()->format('Y-m');
        $branches = PaymentCalendarReportService::normalizeBranches($request->input('branch', []));

        $suffix = $branches === [] ? 'all' : implode('-', $branches);

        return Excel::download(
            new PaymentCalendarReportExport($month, $branches),
            'payment_calendar_report_' . $month . '_' . $suffix . '.xlsx'
        );
    }
}
