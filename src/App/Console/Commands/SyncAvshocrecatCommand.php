<?php

namespace App\Console\Commands;

use App\Jobs\SyncAvshocrecatReportJob;
use Illuminate\Console\Command;

class SyncAvshocrecatCommand extends Command
{
    protected $signature = 'sync:avshocrecat';

    protected $description = 'Execute AVSHOCRECAT stored procedure, truncate the local report table, and import the latest reporting data into PostgreSQL.';

    public function handle(): int
    {
        $this->info('AVSHOCRECAT report synchronization started...');

        SyncAvshocrecatReportJob::dispatchSync();

        $this->info('AVSHOCRECAT report synchronization completed successfully.');

        return self::SUCCESS;
    }
}