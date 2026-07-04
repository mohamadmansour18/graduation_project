<?php

namespace App\Console\Commands;

use App\Services\Notifications\StudyPlanScheduledNotificationService;
use Illuminate\Console\Command;

class SendDailyStudyMotivationNotificationsCommand extends Command
{
    protected $signature = 'study-notifications:daily-motivation';

    protected $description = 'Send daily study motivation notifications at the beginning of the day.';

    public function handle(StudyPlanScheduledNotificationService $service): int
    {
        $sentCount = $service->sendDailyStudyMotivationNotifications();

        $this->info("Daily study motivation notifications sent: {$sentCount}");

        return self::SUCCESS;
    }
}
