<?php

namespace App\Http\Controllers\Api\V1\Reports;

use App\Http\Controllers\Controller;
use App\Models\AvshocrecatReport;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ApiReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = trim((string) $request->input('q', ''));
        $perPage = (int) $request->input('per_page', 20);

        $query = AvshocrecatReport::query();

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

        $rows = $query
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->query());

        return ApiResponse::paginated(
            $rows,
            $rows->getCollection()->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'magazyn' => $row->magazyn,
                    'karz_alyjy' => $row->karz_alyjy,
                    'telefon_belgisi' => $row->telefon_belgisi,
                    'pasport_belgisi' => $row->pasport_belgisi,
                    'sertnama_nomeri' => $row->sertnama_nomeri,
                    'tiger_kody' => $row->tiger_kody,
                    'kt_cykdajy' => $row->kt_cykdajy,
                    'dt_girdeji' => $row->dt_girdeji,
                    'galyndy' => $row->galyndy,
                    'aylyk_tolegi' => $row->aylyk_tolegi,
                    'tolejek_senesi' => optional($row->tolejek_senesi)?->format('Y-m-d'),
                    'statusy' => $row->statusy,
                    'created_at' => optional($row->created_at)?->format('Y-m-d H:i:s'),
                    'updated_at' => optional($row->updated_at)?->format('Y-m-d H:i:s'),
                ];
            })->values(),
            'Report list fetched successfully.'
        );
    }

    public function show(AvshocrecatReport $report)
    {
        return ApiResponse::success([
            'id' => (int) $report->id,
            'magazyn' => $report->magazyn,
            'karz_alyjy' => $report->karz_alyjy,
            'telefon_belgisi' => $report->telefon_belgisi,
            'pasport_belgisi' => $report->pasport_belgisi,
            'sertnama_nomeri' => $report->sertnama_nomeri,
            'tiger_kody' => $report->tiger_kody,
            'kt_cykdajy' => $report->kt_cykdajy,
            'dt_girdeji' => $report->dt_girdeji,
            'm1' => $report->m1,
            'm2' => $report->m2,
            'm3' => $report->m3,
            'm4' => $report->m4,
            'm5' => $report->m5,
            'm6' => $report->m6,
            'galyndy' => $report->galyndy,
            'aylyk_tolegi' => $report->aylyk_tolegi,
            'karz_alan_senesi' => $report->karz_alan_senesi,
            'gutaryan_senesi' => $report->gutaryan_senesi,
            'kategoriyasy' => $report->kategoriyasy,
            'maglumat' => $report->maglumat,
            'bellik' => $report->bellik,
            'tolejek_senesi' => optional($report->tolejek_senesi)?->format('Y-m-d'),
            'statusy' => $report->statusy,
            'created_at' => optional($report->created_at)?->format('Y-m-d H:i:s'),
            'updated_at' => optional($report->updated_at)?->format('Y-m-d H:i:s'),
        ], 'Report detail fetched successfully.');
    }
}