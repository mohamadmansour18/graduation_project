<?php

namespace App\Console\Commands;

use App\Services\Notifications\StudyPlanScheduledNotificationService;
use Illuminate\Console\Command;

class SendStudyTaskMissedNotificationsCommand extends Command
{
    protected $signature = 'study-notifications:task-missed';

    protected $description = 'Mark due study tasks as missed and notify users.';

    public function handle(StudyPlanScheduledNotificationService $service): int
    {
        $processedCount = $service->sendDueTaskMissedNotifications();

        $this->info("Study tasks marked as missed: {$processedCount}");

        return self::SUCCESS;
    }
}
