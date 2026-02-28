<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SyncAvshocrecatReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function handle(): void
    {
        $proc = config('sync.mssql.avshocrecat_proc');      // dbo.AVSHOCRECAT_TEST
        $pasport = (string) config('sync.mssql.avshocrecat_pasport', '');
        $pgTable = config('sync.pgsql.avshocrecat_table');     // avshocrecat_report
        $chunkSize = (int) config('sync.chunk_size', 1000);

        // 1) Call MSSQL stored procedure
        $rows = DB::connection('sqlsrv')->select(
            "EXEC {$proc} @PASPORT = ?",
            [$pasport]
        );

        // 2) Snapshot: reset the target table
        DB::connection('pgsql')->table($pgTable)->truncate();

        if (empty($rows)) {
            return;
        }

        // 3) Insert chunk
        $now = now();
        $buffer = [];

        foreach ($rows as $row) {
            $r = (array) $row;

            $buffer[] = [
                'magazyn' => $r['Magazyn'] ?? null,
                'karz_alyjy' => $r['Karz alyjy'] ?? null,
                'telefon_belgisi' => $r['Telefon belgisi'] ?? null,
                'pasport_belgisi' => $r['Pasport belgisi'] ?? null,
                'sertnama_nomeri' => $r['Sertnama nomeri'] ?? null,
                'tiger_kody' => $r['Tiger Kody'] ?? ($r['Tiger Kodu'] ?? null),

                'kt_cykdajy' => $this->toNumeric($r['KT (cykdajy)'] ?? null),
                'dt_girdeji' => $this->toNumeric($r['DT (girdeji)'] ?? null),

                'm1' => $this->toNumeric($r['1ay'] ?? null),
                'm2' => $this->toNumeric($r['2ay'] ?? null),
                'm3' => $this->toNumeric($r['3ay'] ?? null),
                'm4' => $this->toNumeric($r['4ay'] ?? null),
                'm5' => $this->toNumeric($r['5ay'] ?? null),
                'm6' => $this->toNumeric($r['6ay'] ?? null),

                'galyndy' => $this->toNumeric($r['Galyndy'] ?? null),
                'aylyk_tolegi' => $this->toNumeric($r['Aylyk tolegi'] ?? null),

                'karz_alan_senesi' => $r['Karz alan senesi'] ?? null,
                'gutaryan_senesi' => $r['Gutaryan senesi'] ?? null,

                'kategoriyasy' => $r['Kategoriyasy'] ?? null,
                'maglumat' => $r['Maglumat'] ?? null,
                'bellik' => $r['Bellik'] ?? null,

                'tolejek_senesi' => $this->toDate($r['Tolejek senesi'] ?? null),
                'statusy' => $r['Statusy'] ?? ($r['Status'] ?? null),

                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($buffer) >= $chunkSize) {
                DB::connection('pgsql')->table($pgTable)->insert($buffer);
                $buffer = [];
            }
        }

        if (! empty($buffer)) {
            DB::connection('pgsql')->table($pgTable)->insert($buffer);
        }
    }

    private function toNumeric($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        $v = str_replace(',', '.', trim((string) $value));

        return is_numeric($v) ? (string) $v : null;
    }

    private function toDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $v = trim((string) $value);

        // MSSQL CAST(date) generally returns YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return $v;
        }

        return null;
    }
}
