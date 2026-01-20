<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncCreditsJob;
use App\Jobs\SyncAvshocrecatReportJob;

class SyncRunCommand extends Command
{
    protected $signature = 'sync:run {--passport= : AVSHOCRECAT için PASPORT filtresi (boşsa hepsi)}';
    protected $description = 'MSSQL -> PG senkron: Credits incremental + Avshocrecat report refresh';

    public function handle(): int
    {
        $passport = (string) ($this->option('passport') ?? '');

        // 1) incremental
        SyncCreditsJob::dispatch();

        // 2) report (truncate+insert yapan job)
        SyncAvshocrecatReportJob::dispatch($passport);

        $this->info('Dispatched: SyncCreditsJob + SyncAvshocrecatReportJob (passport=' . ($passport === '' ? 'ALL' : $passport) . ')');
        return self::SUCCESS;
    }
}
