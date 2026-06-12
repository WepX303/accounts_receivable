<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreditsInitLocalFields extends Command
{
    /**
     * Kullanım örnekleri:
     * php artisan credits:init-local
     * php artisan credits:init-local --only-null
     * php artisan credits:init-local --dry-run
     * php artisan credits:init-local --chunk=5000
     */
    protected $signature = 'credits:init-local
        {--only-null : Sadece local alanlar NULL ise doldurur}
        {--dry-run : Güncelleme yapmaz, sadece sayıları gösterir}
        {--chunk=2000 : Parça boyutu}';

    protected $description = 'credits tablosunda amount/paid değerlerini amount_local/paid_local alanlarına ilk kez aktarır (one-time).';

    public function handle(): int
    {
        $table = (string) (config('sync.pgsql.credits_table') ?: 'credits');
        $chunk = (int) $this->option('chunk');
        $onlyNull = (bool) $this->option('only-null');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("Table: {$table}");
        $this->info("Chunk: {$chunk}");
        $this->info('Only NULL locals: ' . ($onlyNull ? 'YES' : 'NO'));
        $this->info('Dry-run: ' . ($dryRun ? 'YES' : 'NO'));

        // Güncellenecek kayıtları say
        $countQuery = DB::connection('pgsql')->table($table);

        if ($onlyNull) {
            $countQuery->where(function ($q) {
                $q->whereNull('amount_local')
                    ->orWhereNull('paid_local');
            });
        }

        $total = (int) $countQuery->count();
        $this->info("Aday kayıt sayısı: {$total}");

        if ($total === 0) {
            $this->info('Güncellenecek kayıt yok.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('Dry-run açık: DB güncellemesi yapılmayacak.');

            return self::SUCCESS;
        }

        $updatedTotal = 0;

        DB::connection('pgsql')->table($table)
            ->select('logicalref') // sadece pk çekiyoruz
            ->when($onlyNull, function ($q) {
                $q->where(function ($qq) {
                    $qq->whereNull('amount_local')
                        ->orWhereNull('paid_local');
                });
            })
            ->orderBy('logicalref')
            ->chunkById($chunk, function ($rows) use (&$updatedTotal, $table, $onlyNull) {
                $ids = $rows->pluck('logicalref')->all();
                if (empty($ids)) {
                    return;
                }

                // Tek bir UPDATE ile chunk güncelle
                // $update = DB::connection('pgsql')->table($table)->whereIn('logicalref', $ids);
                $update = DB::connection('pgsql')->table($table)->whereIn('source_id', $ids);

                if ($onlyNull) {
                    // sadece null olanları doldur: COALESCE ile
                    $affected = $update->update([
                        'amount_local' => DB::raw('COALESCE(amount_local, amount)'),
                        'paid_local' => DB::raw('COALESCE(paid_local, paid)'),
                        'updated_at' => now(),
                    ]);
                } else {
                    // hepsini overwrite et (one-time hızlı başlangıç için)
                    $affected = $update->update([
                        'amount_local' => DB::raw('amount'),
                        'paid_local' => DB::raw('paid'),
                        'updated_at' => now(),
                    ]);
                }

                $updatedTotal += (int) $affected;
                $this->line("Chunk güncellendi: {$affected} kayıt");
            }, 'logicalref'); // primary key'in adı

        $this->info("Toplam güncellenen: {$updatedTotal}");

        return self::SUCCESS;
    }
}

/**
 * Kullanım örnekleri:
 *
 * Önce “kaç kayıt etkilenecek” gör
 * / php artisan credits:init-local --dry-run --only-null
 *
 * Sonra sadece NULL olanları doldur (en güvenlisi)
 * / php artisan credits:init-local --only-null
 *
 * İstersen hepsini overwrite et (one-time, daha agresif)
 * / php artisan credits:init-local
 */
