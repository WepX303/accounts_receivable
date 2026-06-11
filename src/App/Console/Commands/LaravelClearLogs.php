<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LaravelClearLogs extends Command
{
    protected $signature = 'logs:clear';

    protected $description = 'Clear the Laravel log file';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $logFile = storage_path('logs/laravel.log');

        if (File::exists($logFile)) {
            File::put($logFile, '');
            $this->info('Laravel log file cleared.');
        } else {
            $this->info('Log file does not exist.');
        }
    }
}
