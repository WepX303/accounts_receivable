<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ClearAllCaches extends Command
{
    protected $signature = 'clear:all';

    protected $description = 'Clear all Laravel caches (view, config, route, etc.)';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Clearing caches...');

        // Clear the view cache
        Artisan::call('view:clear');
        $this->info('View cache cleared.');

        // Clear the config cache
        Artisan::call('config:clear');
        $this->info('Config cache cleared.');

        // Clear the route cache
        Artisan::call('route:clear');
        $this->info('Route cache cleared.');

        // Clear the cache (general application cache)
        Artisan::call('cache:clear');
        $this->info('Application cache cleared.');

        $this->info('All caches cleared successfully.');
    }
}