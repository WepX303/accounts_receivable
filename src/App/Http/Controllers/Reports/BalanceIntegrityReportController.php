<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\BalanceIntegrityReportService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BalanceIntegrityReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'branch' => 'nullable|string|max:100',
            'q' => 'nullable|string|max:255',
            'min_diff' => 'nullable|numeric|min:0',
            'only_mismatch' => 'nullable|in:0,1',
            'sort' => 'nullable|in:diff_desc,diff_asc,customer_asc,branch_asc,recent',
        ]);

        $service = new BalanceIntegrityReportService($request);

        $rows = $service->rows();
        $summary = $service->summary($rows);
        $branchRows = $service->branchRows($rows);

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

        return view('pages.reports.balance-integrity.index', [
            'rows' => $paginatedRows,
            'summary' => $summary,
            'branchRows' => $branchRows,
            'branches' => $service->branches(),
            'dateFrom' => $service->dateFrom(),
            'dateTo' => $service->dateTo(),
            'branch' => trim((string) $request->get('branch', '')),
            'q' => trim((string) $request->get('q', '')),
            'minDiff' => $request->get('min_diff'),
            'onlyMismatch' => $request->get('only_mismatch', '1') === '1',
            'sort' => (string) $request->get('sort', 'diff_desc'),
        ]);
    }
}
