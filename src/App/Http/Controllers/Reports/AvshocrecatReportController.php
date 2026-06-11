<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AvshocrecatReport;
use Illuminate\Http\Request;

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
        set_time_limit(0);

        $q = trim((string) $request->query('q', ''));

        $filename = 'avshocrecat_report_' . now()->format('Ymd_His') . '.csv';

        $query = AvshocrecatReport::query()
            ->select($this->exportColumns())
            ->orderByDesc('id');

        if ($q !== '') {
            $this->applySearch($query, $q);
        }

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                abort(500, 'Export output could not be opened.');
            }

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, $this->exportHeadings(), ';');

            foreach ($query->cursor() as $r) {
                fputcsv($handle, [
                    $this->cleanCsvValue($r->magazyn),
                    $this->cleanCsvValue($r->karz_alyjy),
                    $this->cleanCsvValue($r->telefon_belgisi),
                    $this->cleanCsvValue($r->pasport_belgisi),
                    $this->cleanCsvValue($r->sertnama_nomeri),
                    $this->cleanCsvValue($r->tiger_kody),
                    $this->cleanCsvValue($r->kt_cykdajy),
                    $this->cleanCsvValue($r->dt_girdeji),
                    $this->cleanCsvValue($r->m1),
                    $this->cleanCsvValue($r->m2),
                    $this->cleanCsvValue($r->m3),
                    $this->cleanCsvValue($r->m4),
                    $this->cleanCsvValue($r->m5),
                    $this->cleanCsvValue($r->m6),
                    $this->cleanCsvValue($r->galyndy),
                    $this->cleanCsvValue($r->aylyk_tolegi),
                    $this->cleanCsvValue($r->karz_alan_senesi),
                    $this->cleanCsvValue($r->gutaryan_senesi),
                    $this->cleanCsvValue($r->kategoriyasy),
                    $this->cleanCsvValue($r->maglumat),
                    $this->cleanCsvValue($r->bellik),
                    $this->cleanCsvValue($r->tolejek_senesi),
                    $this->cleanCsvValue($r->statusy),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
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

    private function exportColumns(): array
    {
        return [
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
        ];
    }

    private function exportHeadings(): array
    {
        return [
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
        ];
    }

    private function cleanCsvValue($value): string
    {
        if ($value === null) {
            return '';
        }

        $value = trim((string) $value);

        if ($value === 'None' || $value === '[NULL]') {
            return '';
        }

        // CSV/Excel formula injection protection
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            $value = "'" . $value;
        }

        return $value;
    }
}
