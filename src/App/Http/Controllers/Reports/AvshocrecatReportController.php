<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\AvshocrecatReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Throwable;

class AvshocrecatReportController extends Controller
{
    /**
     * A worksheet cannot hold more than 1.048.576 rows, so refuse anything that
     * would silently produce a truncated file.
     */
    private const MAX_EXPORT_ROWS = 1000000;

    /** Rows fetched per database round-trip while streaming the file. */
    private const EXPORT_CHUNK = 2000;

    /** How long a single user may hold the export lock. */
    private const EXPORT_LOCK_SECONDS = 900;

    /** Time budget granted to each chunk, so a stuck export cannot hang forever. */
    private const EXPORT_CHUNK_TIME_LIMIT = 60;

    /** Age at which a leftover temp file is considered abandoned. */
    private const STALE_EXPORT_SECONDS = 3600;

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

        if ($total > self::MAX_EXPORT_ROWS) {
            return $this->exportError($request, __('pages/reports.avshocrecat.export_too_large', [
                'count' => number_format($total),
                'limit' => number_format(self::MAX_EXPORT_ROWS),
            ]));
        }

        // One export per user at a time: repeated clicks must not pile up heavy writes.
        $lock = Cache::lock(
            'avshocrecat-export:' . ($request->user()?->id ?? $request->ip()),
            self::EXPORT_LOCK_SECONDS
        );

        if (! $lock->get()) {
            return $this->exportError($request, __('pages/reports.avshocrecat.export_in_progress'));
        }

        try {
            $path = $this->writeXlsx($q, $dateFrom, $dateTo, $branches);
        } catch (Throwable $e) {
            report($e);

            return $this->exportError($request, __('pages/reports.avshocrecat.export_failed'));
        } finally {
            $lock->release();
        }

        return response()
            ->download($path, 'avshocrecat_report_' . now()->format('Ymd_His') . '.xlsx')
            ->deleteFileAfterSend();
    }

    /**
     * Streams the whole result set into an .xlsx file on disk.
     *
     * Memory stays flat regardless of row count: rows are pulled in keyset
     * paginated chunks and handed straight to the writer, which appends them to
     * the sheet instead of buffering the workbook.
     */
    private function writeXlsx(string $q, ?string $dateFrom, ?string $dateTo, array $branches): string
    {
        $directory = storage_path('app/tmp/exports');

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Export directory could not be created: {$directory}");
        }

        $this->pruneStaleExports($directory);

        $path = $directory . DIRECTORY_SEPARATOR . Str::uuid() . '.xlsx';

        // Inline strings keep the writer from accumulating a shared-string table
        // in memory, which is what makes the footprint independent of row count.
        $writer = new Writer(new Options(SHOULD_USE_INLINE_STRINGS: true));
        $writer->openToFile($path);

        try {
            $writer->addRow(
                Row::fromValuesWithStyle($this->exportHeadings(), new Style(fontBold: true))
            );

            $lastId = null;

            do {
                set_time_limit(self::EXPORT_CHUNK_TIME_LIMIT);

                $chunk = $this->filteredQuery($q, $dateFrom, $dateTo, $branches)
                    ->select($this->exportColumns())
                    ->when($lastId !== null, fn ($query) => $query->where('id', '<', $lastId))
                    ->orderByDesc('id')
                    ->limit(self::EXPORT_CHUNK)
                    ->toBase()
                    ->get();

                foreach ($chunk as $row) {
                    $writer->addRow(Row::fromValues($this->exportRow($row)));
                }

                $lastId = $chunk->last()?->id;
                $fetched = $chunk->count();

                unset($chunk);
            } while ($fetched === self::EXPORT_CHUNK && $lastId !== null);

            $writer->close();
        } catch (Throwable $e) {
            // Drop the half-written file so no truncated workbook is ever served.
            $writer->close();
            @unlink($path);

            throw $e;
        }

        return $path;
    }

    /**
     * A download that is aborted mid-transfer leaves its temp file behind, so
     * sweep anything older than the longest an export is allowed to take.
     */
    private function pruneStaleExports(string $directory): void
    {
        $cutoff = time() - self::STALE_EXPORT_SECONDS;

        foreach (glob($directory . DIRECTORY_SEPARATOR . '*.xlsx') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    private function exportError(Request $request, string $message)
    {
        return redirect()
            ->to($request->headers->get('referer') ?: route('report'))
            ->with('export_error', $message);
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

    private function exportColumns(): array
    {
        return [
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
        ];
    }

    private function exportHeadings(): array
    {
        return [
            __('pages/monthly_report.th.id'),
            __('pages/monthly_report.th.store'),
            __('pages/monthly_report.th.borrower'),
            __('pages/monthly_report.th.phone'),
            __('pages/monthly_report.th.passport'),
            __('pages/monthly_report.th.contract'),
            __('pages/monthly_report.th.tiger'),
            __('pages/monthly_report.th.kt_expense'),
            __('pages/monthly_report.th.dt_income'),
            __('pages/monthly_report.th.m1'),
            __('pages/monthly_report.th.m2'),
            __('pages/monthly_report.th.m3'),
            __('pages/monthly_report.th.m4'),
            __('pages/monthly_report.th.m5'),
            __('pages/monthly_report.th.m6'),
            __('pages/monthly_report.th.balance'),
            __('pages/monthly_report.th.monthly_payment'),
            __('pages/monthly_report.th.loan_date'),
            __('pages/monthly_report.th.end_date'),
            __('pages/monthly_report.th.category'),
            __('pages/monthly_report.th.info'),
            __('pages/monthly_report.th.note'),
            __('pages/monthly_report.th.will_pay_date'),
            __('pages/monthly_report.th.status'),
            __('pages/monthly_report.th.created'),
            __('pages/monthly_report.th.updated'),
        ];
    }

    private function exportRow(object $row): array
    {
        return [
            (int) $row->id,
            $row->magazyn,
            $row->karz_alyjy,
            $row->telefon_belgisi,
            $row->pasport_belgisi,
            $row->sertnama_nomeri,
            $row->tiger_kody,
            $this->numeric($row->kt_cykdajy),
            $this->numeric($row->dt_girdeji),
            $this->numeric($row->m1),
            $this->numeric($row->m2),
            $this->numeric($row->m3),
            $this->numeric($row->m4),
            $this->numeric($row->m5),
            $this->numeric($row->m6),
            $this->numeric($row->galyndy),
            $this->numeric($row->aylyk_tolegi),
            $row->karz_alan_senesi,
            $row->gutaryan_senesi,
            $row->kategoriyasy,
            $row->maglumat,
            $row->bellik,
            $row->tolejek_senesi,
            $row->statusy,
            $this->timestamp($row->created_at),
            $this->timestamp($row->updated_at),
        ];
    }

    /**
     * Amounts are stored as text in places, so normalise them into real numbers
     * and let Excel treat the column as numeric.
     */
    private function numeric($value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (float) str_replace([',', ' '], ['.', ''], trim((string) $value));
    }

    private function timestamp($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return rescue(
            fn () => Carbon::parse($value)->format('Y-m-d H:i:s'),
            (string) $value,
            false
        );
    }
}
