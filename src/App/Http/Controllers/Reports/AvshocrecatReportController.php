<?php

namespace App\Http\Controllers\Reports;

use App\Exports\AvshocrecatReportExport;
use App\Http\Controllers\Controller;
use App\Models\AvshocrecatReport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AvshocrecatReportController extends Controller
{
    private int $excelLimit = 5000;

    public function index(Request $request)
    {
        $data = $this->validatedFilters($request);

        $q = trim((string) ($data['q'] ?? ''));
        $dateFrom = $data['date_from'] ?? null;
        $dateTo = $data['date_to'] ?? null;
        $selectedBranches = collect($data['branches'] ?? [])->filter()->values()->all();

        $query = $this->filteredQuery($q, $dateFrom, $dateTo, $selectedBranches);

        $rows = $query
            ->orderByDesc('id')
            ->paginate(25)
            ->appends($request->query());

        $branches = AvshocrecatReport::query()
            ->whereNotNull('magazyn')
            ->where('magazyn', '!=', '')
            ->distinct()
            ->orderBy('magazyn')
            ->pluck('magazyn');

        return view('pages.reports.avshocrecat.index', [
            'rows' => $rows,
            'branches' => $branches,
            'selectedBranches' => $selectedBranches,
            'q' => $q,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function export(Request $request)
    {
        $data = $this->validatedFilters($request);

        $q = trim((string) ($data['q'] ?? ''));
        $dateFrom = $data['date_from'] ?? null;
        $dateTo = $data['date_to'] ?? null;
        $branches = collect($data['branches'] ?? [])->filter()->values()->all();

        $total = $this->filteredQuery($q, $dateFrom, $dateTo, $branches)->count();

        if ($total > $this->excelLimit) {
            return $this->downloadCsv($q, $dateFrom, $dateTo, $branches);
        }

        return Excel::download(
            new AvshocrecatReportExport(
                q: $q,
                dateFrom: $dateFrom,
                dateTo: $dateTo,
                branches: $branches
            ),
            'avshocrecat_report_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'q' => 'nullable|string|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'branches' => 'nullable|array',
            'branches.*' => 'nullable|string|max:100',
        ]);
    }

    private function filteredQuery(string $q = '', ?string $dateFrom = null, ?string $dateTo = null, array $branches = [])
    {
        $query = AvshocrecatReport::query();

        if ($q !== '') {
            $this->applySearch($query, $q);
        }

        if ($dateFrom) {
            $query->whereDate('tolejek_senesi', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('tolejek_senesi', '<=', $dateTo);
        }

        if (! empty($branches)) {
            $query->whereIn('magazyn', $branches);
        }

        return $query;
    }

    private function applySearch($query, string $q): void
    {
        $q = mb_substr($q, 0, 100);
        $like = "%{$q}%";

        $query->where(function ($sub) use ($like) {
            $sub->where('karz_alyjy', 'ILIKE', $like)
                ->orWhere('pasport_belgisi', 'ILIKE', $like)
                ->orWhere('telefon_belgisi', 'ILIKE', $like)
                ->orWhere('tiger_kody', 'ILIKE', $like)
                ->orWhere('magazyn', 'ILIKE', $like)
                ->orWhere('maglumat', 'ILIKE', $like)
                ->orWhere('sertnama_nomeri', 'ILIKE', $like)
                ->orWhere('kategoriyasy', 'ILIKE', $like)
                ->orWhere('bellik', 'ILIKE', $like)
                ->orWhere('statusy', 'ILIKE', $like);
        });
    }

    private function downloadCsv(string $q, ?string $dateFrom, ?string $dateTo, array $branches)
    {
        $filename = 'avshocrecat_report_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($q, $dateFrom, $dateTo, $branches) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $this->csvHeadings());

            $this->filteredQuery($q, $dateFrom, $dateTo, $branches)
                ->select([
                    'id',
                    'magazyn',
                    'karz_alyjy',
                    'telefon_belgisi',
                    'pasport_belgisi',
                    'sertnama_nomeri',
                    'tiger_kody',
                    'kt_cykdajy',
                    'dt_girdeji',
                    'm1',
                    'm2',
                    'm3',
                    'm4',
                    'm5',
                    'm6',
                    'galyndy',
                    'aylyk_tolegi',
                    'karz_alan_senesi',
                    'gutaryan_senesi',
                    'kategoriyasy',
                    'maglumat',
                    'bellik',
                    'tolejek_senesi',
                    'statusy',
                    'created_at',
                    'updated_at',
                ])
                ->orderByDesc('id')
                ->chunk(1000, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, $this->csvRow($row));
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function csvHeadings(): array
    {
        return [
            'ID',
            'Magazyn',
            'Karz alyjy',
            'Telefon belgisi',
            'Pasport belgisi',
            'Sertnama nomeri',
            'Tiger kody',
            'KT',
            'DT',
            '1 Ay',
            '2 Ay',
            '3 Ay',
            '4 Ay',
            '5 Ay',
            '6 Ay',
            'Galyndy',
            'Aylyk tolegi',
            'Karz alan senesi',
            'Gutaryan senesi',
            'Kategoriyasy',
            'Maglumat',
            'Bellik',
            'Tolejek senesi',
            'Statusy',
            'Created At',
            'Updated At',
        ];
    }

    private function csvRow($row): array
    {
        return [
            $row->id,
            $row->magazyn,
            $row->karz_alyjy,
            $row->telefon_belgisi,
            $row->pasport_belgisi,
            $row->sertnama_nomeri,
            $row->tiger_kody,
            $row->kt_cykdajy,
            $row->dt_girdeji,
            $row->m1,
            $row->m2,
            $row->m3,
            $row->m4,
            $row->m5,
            $row->m6,
            $row->galyndy,
            $row->aylyk_tolegi,
            $row->karz_alan_senesi,
            $row->gutaryan_senesi,
            $row->kategoriyasy,
            $row->maglumat,
            $row->bellik,
            $row->tolejek_senesi,
            $row->statusy,
            $row->created_at,
            $row->updated_at,
        ];
    }
}
