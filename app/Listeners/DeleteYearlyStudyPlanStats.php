<?php

namespace App\Listeners;

use App\Events\StudyPlanDeleted;
use App\Models\UserYearlyStudyPlanStat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class DeleteYearlyStudyPlanStats implements ShouldQueue
{
    use InteractsWithQueue ;

    public int $tries = 2;
    public array $backoff = [5, 10];
    public int $timeout = 60;
    public string $queue = 'light';
    public function handle(StudyPlanDeleted $event): void
    {
        UserYearlyStudyPlanStat::query()
            ->where('user_id', $event->userId)
            ->where('study_plan_id', $event->studyPlanId)
            ->delete();
    }

    public function failed(StudyPlanDeleted $event, \Throwable $exception): void
    {
        Log::channel('errors')->error('Failed to delete yearly study plan stats after plan deletion', [
            'user_id' => $event->userId,
            'study_plan_id' => $event->studyPlanId,
            'error' => $exception->getMessage(),
        ]);
    }
}
