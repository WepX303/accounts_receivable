<?php

namespace app\Http\Controllers\Reports;

use Illuminate\Http\Request;
use app\Http\Controllers\Controller;
use App\Models\AvshocrecatReport;


class AvshocrecatReportController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        // Base query (tüm kayıtlar)
        $query = AvshocrecatReport::query();

        // Search varsa
        if ($q !== '') {
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

        // Pagination
        $rows = $query
            ->orderByDesc('id')
            ->paginate(25)
            ->appends(['q' => $q]);

        return view('pages.reports.avshocrecat.index', compact('rows'));
    }
}