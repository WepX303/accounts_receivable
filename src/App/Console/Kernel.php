<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        $schedule->command('sync:run')
            ->everyFifteenMinutes()
            ->between('09:00', '22:59')
            ->withoutOverlapping(10)
            ->runInBackground();

        $schedule->command('report:daily-payments')->dailyAt('23:00');
    }


    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    protected $commands = [
        \App\Console\Commands\ClearAllCaches::class,
        \App\Console\Commands\LaravelClearLogs::class,
        \App\Console\Commands\CreditsInitLocalFields::class,
        \App\Console\Commands\SyncRunCommand::class,
        \App\Console\Commands\SendDailyPaymentReportCommand::class,
        \App\Console\Commands\SyncAvshocrecatCommand::class,
        \App\Console\Commands\SyncCreditsCommand::class,
    ];
}
