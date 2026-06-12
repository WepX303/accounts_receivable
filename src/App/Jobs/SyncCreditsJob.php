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
        $pgTable = (string) config('sync.pgsql.credits_table'); // credits_table
        $chunkSize = (int) config('sync.chunk_size', 1000);

        /** @var ConnectionInterface $second_pgsql */
        $sourcePgsql = DB::connection('second_pgsql');
        /** @var ConnectionInterface $pgsql */
        $pgsql = DB::connection('pgsql');

        $stateKey = app()->environment() . '_credits_last_rv_hex';

        $stateRow = $pgsql->table('sync_state')->where('key', $stateKey)->first();
        $lastRv = $stateRow?->value ?: '0000000000000000';

        $query = $sourcePgsql
            ->table('credits')
            // ->selectRaw("*, encode(rv, 'hex') as rv_hex")
            // ->selectRaw("*, encode(rv, 'hex') as rv_hex, (('x' || encode(rv, 'hex'))::bit(64)::bigint) as rv_bigint")
            ->selectRaw("id as source_id, *, encode(rv, 'hex') as rv_hex, (('x' || encode(rv, 'hex'))::bit(64)::bigint) as rv_bigint")
            ->whereNotNull('rv')
            ->whereRaw("rv > decode(?, 'hex')", [$lastRv])
            ->orderBy('rv');

        $maxRvSeen = $lastRv;

        $query->chunk($chunkSize, function ($rows) use (&$maxRvSeen, $pgTable, $pgsql) {
            if ($rows->isEmpty()) {
                return;
            }

            $now = now();

            $sourceIds = [];
            foreach ($rows as $row) {
                $r = (array) $row;
                $sid = (int) ($r['source_id'] ?? 0);
                if ($sid > 0) {
                    $sourceIds[] = $sid;
                }
            }

            $sourceIds = array_values(array_unique($sourceIds));

            if (empty($sourceIds)) {
                return;
            }

            // 2) Retrieve existing records with local fields + timestamps
            $existingRows = $pgsql->table($pgTable)
                ->select([
                    'source_id',
                    'amount_local',
                    'paid_local',
                    'amount_updated_at',
                    'paid_updated_at',
                    'created_at',
                ])
                ->whereIn('source_id', $sourceIds)
                ->get();

            $existingMap = [];
            foreach ($existingRows as $er) {
                // $existingMap[(int) $er->logicalref] = $er;
                $existingMap[(int) $er->source_id] = $er;
            }

            $payload = [];

            foreach ($rows as $row) {
                $r = (array) $row;

                $sourceId = (int) ($r['source_id'] ?? 0);
                $logicalref = (int) ($r['logicalref'] ?? 0);

                if ($sourceId <= 0 || $logicalref <= 0) {
                    continue;
                }

                $rvHex = $r['rv_hex'] ?? null;
                $rv = (int) ($r['rv_bigint'] ?? 0);

                if ($rvHex && strcmp($rvHex, $maxRvSeen) > 0) {
                    $maxRvSeen = $rvHex;
                }

                $amount = $r['amount'] ?? null;
                $paid = $r['paid'] ?? null;

                // $existing = $existingMap[$logicalref] ?? null;
                $existing = $existingMap[$sourceId] ?? null;
                $isExisting = (bool) $existing;

                // SAME KEYS IN EVERY LINE: We set local fields to the default value by default.
                $amountLocal = $isExisting ? $existing->amount_local : null;
                $paidLocal = $isExisting ? $existing->paid_local : null;

                // RULE-1: new registration -> local initialisation
                if (! $isExisting) {
                    $amountLocal = $amount;
                    $paidLocal = $paid;
                } else {
                    // RULE-2: if it's an old record but the local is ‘virgin’ and the remote is now full -> fill the local
                    $amountNeverTouched = is_null($existing->amount_updated_at);
                    $paidNeverTouched = is_null($existing->paid_updated_at);

                    if (is_null($amountLocal) && $amountNeverTouched && ! is_null($amount)) {
                        $amountLocal = $amount;
                    }
                    if (is_null($paidLocal) && $paidNeverTouched && ! is_null($paid)) {
                        $paidLocal = $paid;
                    }
                }

                $payload[] = [
                    'source_id' => $sourceId,
                    'logicalref' => $logicalref,
                    'branch' => $r['branch'] ?? null,
                    'name' => $r['name_'] ?? null,
                    'passport' => $r['passport_'] ?? null,
                    'phone' => $r['phone'] ?? null,
                    'contract' => $r['contract_'] ?? null,
                    'date_' => $r['date_'] ?? null,

                    'amount' => $amount,
                    'paid' => $paid,

                    'willpaiddate' => $r['willpaiddate'] ?? null,
                    'willpaidamount' => $r['willpaidamount'] ?? null,
                    'note' => $r['note'] ?? null,
                    'lastnoteddate' => $r['lastnoteddate'] ?? null,
                    'status' => $r['status'] ?? null,
                    'active' => isset($r['active']) ? (bool) $r['active'] : false,
                    'is_blocked' => (int) ($r['is_blocked'] ?? 0),
                    'initiator_i' => $r['initiator_i'] ?? null,
                    'clientref' => $r['clientref'] ?? null,
                    'custstatus' => $r['custstatus'] ?? null,
                    'assurance' => $r['assurance'] ?? null,
                    'ctype' => $r['ctype'] ?? null,
                    'cardno' => $r['cardno'] ?? null,
                    'fishno' => $r['fishno'] ?? null,
                    'manager' => $r['MANAGER'] ?? null,
                    'confirmedby' => $r['confirmedby'] ?? null,
                    'gstatus' => $r['gstatus'] ?? null,

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
                ['source_id'],
                [
                    'logicalref',
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
                    'is_blocked',
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
