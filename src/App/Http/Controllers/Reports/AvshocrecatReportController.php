<?php

namespace App\Http\Controllers\Reports;

use App\Exports\AvshocrecatReportExport;
use App\Http\Controllers\Controller;
use App\Models\AvshocrecatReport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AvshocrecatReportController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $query = AvshocrecatReport::query();

        if ($q !== '') {
            $this->applySearch($query, $q);
        }

        $rows = $query
            ->orderByDesc('id')
            ->paginate(25)
            ->appends(['q' => $q]);

        return view('pages.reports.avshocrecat.index', compact('rows'));
    }

    public function export(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $filename = 'avshocrecat_report_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new AvshocrecatReportExport($q), $filename);
    }

    private function applySearch($query, string $q): void
    {
        $q = mb_substr($q, 0, 100);

        $query->where(function ($sub) use ($q) {
            $sub->where('karz_alyjy', 'ILIKE', "%{$q}%")
                ->orWhere('pasport_belgisi', 'ILIKE', "%{$q}%")
                ->orWhere('telefon_belgisi', 'ILIKE', "%{$q}%")
                ->orWhere('tiger_kody', 'ILIKE', "%{$q}%")
                ->orWhere('magazyn', 'ILIKE', "%{$q}%")
                ->orWhere('maglumat', 'ILIKE', "%{$q}%")
                ->orWhere('sertnama_nomeri', 'ILIKE', "%{$q}%");
        });
    }
}