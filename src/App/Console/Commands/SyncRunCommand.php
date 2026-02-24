<?php

namespace App\Console\Commands;

use App\Jobs\SyncAvshocrecatReportJob;
use App\Jobs\SyncCreditsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncRunCommand extends Command
{
    protected $signature = 'sync:run {--passport= : AVSHOCRECAT için PASPORT filtresi (boşsa hepsi)}';

    protected $description = 'MSSQL -> PG senkron: Credits incremental + Avshocrecat report refresh';

    // public function handle(): int
    // {
    //     $passport = (string) ($this->option('passport') ?? '');

    //     // 1) incremental
    //     SyncCreditsJob::dispatch();

    //     // 2) report (truncate+insert yapan job)
    //     SyncAvshocrecatReportJob::dispatch($passport);

    //     $this->info('Dispatched: SyncCreditsJob + SyncAvshocrecatReportJob (passport='.($passport === '' ? 'ALL' : $passport).')');

    //     return self::SUCCESS;
    // }

    public function handle(): int
    {
        $lock = Cache::lock('sync:run-lock', 14 * 60); // 14 dk
        if (! $lock->get()) {
            $this->warn('sync:run already running, skipped.');
            return self::SUCCESS;
        }

        try {
            $passport = (string) ($this->option('passport') ?? '');

            SyncCreditsJob::dispatch();
            SyncAvshocrecatReportJob::dispatch(); // dispatch($passport) kullanmıyorsan böyle kalsın

            $this->info('Dispatched: SyncCreditsJob + SyncAvshocrecatReportJob');
            return self::SUCCESS;
        } finally {
            optional($lock)->release();
        }
    }
}

// For get data from command line, run:

//  php artisan sync:run --passport=   (all passports)
//  or
//  php artisan sync:run --passport=123456

// To process the jobs in the queue, run:
// php artisan queue:work --stop-when-empty
