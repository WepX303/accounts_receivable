<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\VoidCorrectionReportService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class VoidCorrectionReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'branch' => 'nullable|string|max:100',
            'cashier' => 'nullable|string|max:255',
            'actor' => 'nullable|string|max:255',
            'type' => 'nullable|in:all,void,correction',
            'q' => 'nullable|string|max:255',
        ]);

        $service = new VoidCorrectionReportService($request);

        $rows = $service->rows();
        $summary = $service->summary($rows);
        $actorRows = $service->actorRows($rows);

        $perPage = 50;
        $page = LengthAwarePaginator::resolveCurrentPage();

        $paginatedRows = new LengthAwarePaginator(
            $rows->slice(($page - 1) * $perPage, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('pages.reports.void-correction.index', [
            'rows' => $paginatedRows,
            'summary' => $summary,
            'actorRows' => $actorRows,
            'branches' => $service->branches(),
            'cashiers' => $service->cashiers(),
            'actors' => $service->actors(),
            'dateFrom' => $service->dateFrom(),
            'dateTo' => $service->dateTo(),
            'branch' => trim((string) $request->get('branch', '')),
            'cashier' => trim((string) $request->get('cashier', '')),
            'actor' => trim((string) $request->get('actor', '')),
            'type' => (string) $request->get('type', 'all'),
            'q' => trim((string) $request->get('q', '')),
        ]);
    }
}
