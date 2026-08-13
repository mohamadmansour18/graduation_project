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
//        $schedule->command('pdf-cache:cleanup-test-downloads --days=7')
//            ->dailyAt('03:00')
//            ->runInBackground();
//
//        $schedule->command('tests:cleanup-stale-review-statuses --hours=48 --limit=200')
//            ->hourly()
//            ->withoutOverlapping(60)
//            ->runInBackground();
//
//        $schedule->command('library-materials:cleanup-stale-review-statuses --hours=48 --limit=200')
//            ->hourly()
//            ->withoutOverlapping(60)
//            ->runInBackground();
//
//        $schedule->command('study-notifications:task-start')
//            ->everyMinute()
//            ->withoutOverlapping()
//            ->runInBackground();
//
//        $schedule->command('study-notifications:task-missed')
//            ->everyMinute()
//            ->withoutOverlapping()
//            ->runInBackground();
//
//        $schedule->command('study-notifications:daily-motivation')
//            ->dailyAt('00:00')
//            ->withoutOverlapping()
//            ->runInBackground();
//
        $schedule
            ->command('backup:run --only-db')
//            ->weeklyOn(5, '04:00')
            ->everyTwoMinutes()
            ->withoutOverlapping(180);
//
//        $schedule
//            ->command('backup:clean')
//            ->weeklyOn(5, '05:00')
//            ->withoutOverlapping(60);
//
//        $schedule
//            ->command('backup:monitor')
//            ->dailyAt('06:00')
//            ->withoutOverlapping(30);
//
//        $schedule->command('model:prune', [
//            '--model' => [
//                \App\Models\ScheduledNotificationDelivery::class,
//                \App\Models\PrunableDatabaseNotification::class,
//            ],
//        ])
//            ->dailyAt('03:30')
//            ->withoutOverlapping(30)
//            ->runInBackground();
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
