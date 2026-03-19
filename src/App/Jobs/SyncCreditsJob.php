<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\ConnectionInterface;
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
        $mssqlTable = (string) config('sync.mssql.credits_table'); // dbo.CREDITS
        $pgTable = (string) config('sync.pgsql.credits_table'); // credits_table
        $chunkSize = (int) config('sync.chunk_size', 1000);

        /** @var ConnectionInterface $sqlsrv */
        $sqlsrv = DB::connection('sqlsrv');
        /** @var ConnectionInterface $pgsql */
        $pgsql = DB::connection('pgsql');

        // Secure the MSSQL DB context
        $dbName = (string) config('database.connections.sqlsrv.database');
        $sqlsrv->statement("USE [$dbName]");

        // Environment-based state key (to prevent mixing of production and test environments)
        $stateKey = app()->environment() . '_credits_last_rv';

        $stateRow = $pgsql->table('sync_state')->where('key', $stateKey)->first();
        $lastRv = $stateRow?->value ? (int) $stateRow->value : 0;

        $query = $sqlsrv
            ->table($mssqlTable)
            ->selectRaw('*, CONVERT(bigint, CONVERT(binary(8), RV)) as rv_bigint')
            ->whereRaw('CONVERT(bigint, CONVERT(binary(8), RV)) > ?', [$lastRv])
            ->orderByRaw('rv_bigint');

        $maxRvSeen = $lastRv;

        $query->chunk($chunkSize, function ($rows) use (&$maxRvSeen, $pgTable, $pgsql) {
            if ($rows->isEmpty()) {
                return;
            }

            $now = now();

            // 1) Logicalref list
            $logicalRefs = [];
            foreach ($rows as $row) {
                $r = (array) $row;
                $lr = (int) ($r['LOGICALREF'] ?? 0);
                if ($lr > 0) {
                    $logicalRefs[] = $lr;
                }
            }
            $logicalRefs = array_values(array_unique($logicalRefs));
            if (empty($logicalRefs)) {
                return;
            }

            // 2) Retrieve existing records with local fields + timestamps
            $existingRows = $pgsql->table($pgTable)
                ->select([
                    'logicalref',
                    'amount_local',
                    'paid_local',
                    'amount_updated_at',
                    'paid_updated_at',
                    'created_at',
                ])
                ->whereIn('logicalref', $logicalRefs)
                ->get();

            $existingMap = [];
            foreach ($existingRows as $er) {
                $existingMap[(int) $er->logicalref] = $er;
            }

            $payload = [];

            foreach ($rows as $row) {
                $r = (array) $row;

                $logicalref = (int) ($r['LOGICALREF'] ?? 0);
                if ($logicalref <= 0) {
                    continue;
                }

                $rv = (int) ($r['rv_bigint'] ?? 0);
                if ($rv > $maxRvSeen) {
                    $maxRvSeen = $rv;
                }

                // $amount = $r['AMOUNT'] ?? null;
                // $paid = $r['PAID'] ?? null;

                // $existing = $existingMap[$logicalref] ?? null;
                // $isExisting = (bool) $existing;

                // // SAME KEYS IN EVERY LINE: We set local fields to the default value by default.
                // $amountLocal = $isExisting ? $existing->amount_local : null;
                // $paidLocal = $isExisting ? $existing->paid_local : null;

                // // RULE-1: new registration -> local initialisation
                // if (! $isExisting) {
                //     $amountLocal = $amount;
                //     $paidLocal = $paid;
                // } else {
                //     // RULE-2: if it's an old record but the local is ‘virgin’ and the remote is now full -> fill the local
                //     $amountNeverTouched = is_null($existing->amount_updated_at);
                //     $paidNeverTouched = is_null($existing->paid_updated_at);

                //     if (is_null($amountLocal) && $amountNeverTouched && ! is_null($amount)) {
                //         $amountLocal = $amount;
                //     }
                //     if (is_null($paidLocal) && $paidNeverTouched && ! is_null($paid)) {
                //         $paidLocal = $paid;
                //     }
                // }

                $amount = $r['AMOUNT'] ?? null;
                $paid = $r['PAID'] ?? null;

                $existing = $existingMap[$logicalref] ?? null;
                $isExisting = (bool) $existing;

                // amount_local always follows remote amount
                $amountLocal = $amount;

                // paid_local keeps existing local behavior
                $paidLocal = $isExisting ? $existing->paid_local : null;

                // new record -> initialize paid_local from remote paid
                if (! $isExisting) {
                    $paidLocal = $paid;
                } else {
                    // old record -> only fill paid_local once if still virgin/null
                    $paidNeverTouched = is_null($existing->paid_updated_at);

                    if (is_null($paidLocal) && $paidNeverTouched && ! is_null($paid)) {
                        $paidLocal = $paid;
                    }
                }

                $payload[] = [
                    'logicalref' => $logicalref,
                    'branch' => $r['BRANCH'] ?? null,
                    'name' => $r['NAME_'] ?? null,
                    'passport' => $r['PASSPORT_'] ?? null,
                    'phone' => $r['PHONE'] ?? null,
                    'contract' => $r['CONTRACT_'] ?? null,
                    'date_' => $r['DATE_'] ?? null,

                    'amount' => $amount,
                    'paid' => $paid,

                    'willpaiddate' => $r['WILLPAIDDATE'] ?? null,
                    'willpaidamount' => $r['WILLPAIDAMOUNT'] ?? null,
                    'note' => $r['NOTE'] ?? null,
                    'lastnoteddate' => $r['LASTNOTEDDATE'] ?? null,
                    'status' => $r['STATUS'] ?? null,
                    'active' => isset($r['ACTIVE']) ? (bool) $r['ACTIVE'] : false,
                    'initiator_i' => $r['INITIATOR_I'] ?? null,
                    'clientref' => $r['CLIENTREF'] ?? null,
                    'custstatus' => $r['CUSTSTATUS'] ?? null,
                    'assurance' => $r['ASSURANCE'] ?? null,
                    'ctype' => $r['CTYPE'] ?? null,
                    'cardno' => $r['CARDNO'] ?? null,
                    'fishno' => $r['FISHNO'] ?? null,
                    'manager' => $r['MANAGER'] ?? null,
                    'confirmedby' => $r['CONFIRMEDBY'] ?? null,
                    'gstatus' => $r['GSTATUS'] ?? null,

                    'rv_bigint' => $rv,

                    // local variables are present in EVERY LINE
                    'amount_local' => $amountLocal,
                    'paid_local' => $paidLocal,

                    // created_at is present in every row (we are preserving the value in the database for existing records)
                    'created_at' => $isExisting ? $existing->created_at : $now,
                    'updated_at' => $now,
                ];
            }

            if (empty($payload)) {
                return;
            }

            // created_at will not be updated!
            $pgsql->table($pgTable)->upsert(
                $payload,
                ['logicalref'],
                [
                    'branch',
                    'name',
                    'passport',
                    'phone',
                    'contract',
                    'date_',
                    'amount',
                    'paid',
                    'willpaiddate',
                    'willpaidamount',
                    'note',
                    'lastnoteddate',
                    'status',
                    'active',
                    'initiator_i',
                    'clientref',
                    'custstatus',
                    'assurance',
                    'ctype',
                    'cardno',
                    'fishno',
                    'manager',
                    'confirmedby',
                    'gstatus',
                    'rv_bigint',
                    'amount_local',
                    'paid_local',
                    'updated_at',
                ]
            );
        });

        // sync_state: Let's not overwrite created_at every time
        $now = now();
        $existingState = $pgsql->table('sync_state')->where('key', $stateKey)->first();

        if ($existingState) {
            $pgsql->table('sync_state')
                ->where('key', $stateKey)
                ->update(['value' => (string) $maxRvSeen, 'updated_at' => $now]);
        } else {
            $pgsql->table('sync_state')
                ->insert([
                    'key' => $stateKey,
                    'value' => (string) $maxRvSeen,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
        }
    }
}
