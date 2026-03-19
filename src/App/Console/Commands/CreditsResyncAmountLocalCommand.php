<?php

namespace App\Console\Commands;

use App\Services\Credits\CreditsResyncAmountLocalService;
use Illuminate\Console\Command;

class CreditsResyncAmountLocalCommand extends Command
{
    protected $signature = 'credits:resync-amount-local';

    protected $description = 'Sync credits.amount_local with credits.amount for mismatched records';

    public function handle(CreditsResyncAmountLocalService $service): int
    {
        $this->info('Starting credits amount_local resync...');

        $result = $service->run();

        $this->line('Affected count: ' . $result['affected_count']);
        $this->line('Updated count: ' . $result['updated_count']);
        $this->info($result['message']);

        return self::SUCCESS;
    }
}