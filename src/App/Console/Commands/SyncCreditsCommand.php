<?php

namespace App\Console\Commands;

use App\Jobs\SyncCreditsJob;
use Illuminate\Console\Command;

class SyncCreditsCommand extends Command
{
    protected $signature = 'sync:credits';

    protected $description = 'Incremental Credits synchronization. Reads new/updated records from SECOND_PGSQL_HOST and updates the local Credits table.';

    public function handle(): int
    {
        $this->info('Credits sync started...');

        SyncCreditsJob::dispatchSync();

        $this->info('Credits sync completed successfully.');

        return self::SUCCESS;
    }
}