<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SyncCreditsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 120;

    public function handle(): void
    {
        $mssqlTable = (string) config('sync.mssql.credits_table'); // dbo.CREDITS_TEST
        $pgTable    = (string) config('sync.pgsql.credits_table'); // credits_test
        $chunkSize  = (int) config('sync.chunk_size', 1000);

        // MSSQL DB context'i garantiye al
        $dbName = (string) config('database.connections.sqlsrv.database'); // BPA
        DB::connection('sqlsrv')->statement("USE [$dbName]");

        // ortam bazlı state key (prod/test karışmasın)
        $stateKey = app()->environment() . '_credits_last_rv';

        $stateRow = DB::connection('pgsql')->table('sync_state')->where('key', $stateKey)->first();
        $lastRv = $stateRow?->value ? (int) $stateRow->value : 0;

        $query = DB::connection('sqlsrv')
            ->table($mssqlTable) // burada DB::raw kullanma!
            ->selectRaw('*, CONVERT(bigint, RV) as rv_bigint')
            ->whereRaw('CONVERT(bigint, RV) > ?', [$lastRv])
            ->orderByRaw('RV');

        $maxRvSeen = $lastRv;

        $query->chunk($chunkSize, function ($rows) use (&$maxRvSeen, $pgTable) {
            if ($rows->isEmpty()) {
                return;
            }

            $payload = [];
            $now = now();

            foreach ($rows as $row) {
                $r = (array) $row;

                $rv = (int) ($r['rv_bigint'] ?? 0);
                if ($rv > $maxRvSeen) {
                    $maxRvSeen = $rv;
                }

                $payload[] = [
                    'logicalref'     => (int) ($r['LOGICALREF'] ?? 0),
                    'branch'         => $r['BRANCH'] ?? null,
                    'name'           => $r['NAME_'] ?? null,
                    'passport'       => $r['PASSPORT_'] ?? null,
                    'phone'          => $r['PHONE'] ?? null,
                    'contract'       => $r['CONTRACT_'] ?? null,
                    'date_'          => $r['DATE_'] ?? null,
                    'amount'         => $r['AMOUNT'] ?? null,
                    'paid'           => $r['PAID'] ?? null,
                    'willpaiddate'   => $r['WILLPAIDDATE'] ?? null,
                    'willpaidamount' => $r['WILLPAIDAMOUNT'] ?? null,
                    'note'           => $r['NOTE'] ?? null,
                    'lastnoteddate'  => $r['LASTNOTEDDATE'] ?? null,
                    'status'         => $r['STATUS'] ?? null,
                    'active'         => isset($r['ACTIVE']) ? (bool) $r['ACTIVE'] : false,
                    'initiator_i'    => $r['INITIATOR_I'] ?? null,
                    'clientref'      => $r['CLIENTREF'] ?? null,
                    'custstatus'     => $r['CUSTSTATUS'] ?? null,
                    'assurance'      => $r['ASSURANCE'] ?? null,
                    'ctype'          => $r['CTYPE'] ?? null,
                    'cardno'         => $r['CARDNO'] ?? null,
                    'fishno'         => $r['FISHNO'] ?? null,
                    'manager'        => $r['MANAGER'] ?? null,
                    'confirmedby'    => $r['CONFIRMEDBY'] ?? null,
                    'gstatus'        => $r['GSTATUS'] ?? null,
                    'rv_bigint'      => $rv,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            DB::connection('pgsql')->table($pgTable)->upsert(
                $payload,
                ['logicalref'],
                [
                    'branch','name','passport','phone','contract','date_','amount','paid',
                    'willpaiddate','willpaidamount','note','lastnoteddate','status','active',
                    'initiator_i','clientref','custstatus','assurance','ctype','cardno','fishno',
                    'manager','confirmedby','gstatus','rv_bigint','updated_at'
                ]
            );
        });

        DB::connection('pgsql')->table('sync_state')->updateOrInsert(
            ['key' => $stateKey],
            ['value' => (string) $maxRvSeen, 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
