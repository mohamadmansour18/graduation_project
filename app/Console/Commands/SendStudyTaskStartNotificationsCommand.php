<?php

namespace App\Console\Commands;

use App\Services\Notifications\StudyPlanScheduledNotificationService;
use Illuminate\Console\Command;

class SendStudyTaskStartNotificationsCommand extends Command
{
    protected $signature = 'study-notifications:task-start';

    protected $description = 'Send study task start notifications for due task occurrences.';

    public function handle(StudyPlanScheduledNotificationService $service): int
    {
        $sentCount = $service->sendDueTaskStartNotifications();

        $this->info("Study task start notifications sent: {$sentCount}");

        return self::SUCCESS;
    }
}
