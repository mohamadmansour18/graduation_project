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
        $schedule->command('pdf-cache:cleanup-test-downloads --days=7')
            ->dailyAt('03:00')
            ->runInBackground();

        $schedule->command('study-notifications:task-start')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('study-notifications:task-missed')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('study-notifications:daily-motivation')
            ->dailyAt('00:00')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('model:prune', [
            '--model' => [
                \App\Models\ScheduledNotificationDelivery::class,
                \App\Models\PrunableDatabaseNotification::class,
            ],
        ])
            ->dailyAt('03:30')
            ->withoutOverlapping(30)
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
