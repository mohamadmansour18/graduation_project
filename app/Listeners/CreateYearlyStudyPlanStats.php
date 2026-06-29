<?php

namespace App\Listeners;

use App\Events\StudyPlanCreated;
use App\Models\UserYearlyStudyPlanStat;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CreateYearlyStudyPlanStats implements ShouldQueue
{
    use InteractsWithQueue ;

    public int $tries = 2;

    public function handle(StudyPlanCreated $event): void
    {
        $year = (int) date('Y', strtotime($event->startDate));

        UserYearlyStudyPlanStat::query()->updateOrCreate(
            [
                'study_plan_id' => $event->studyPlanId,
                'year' => $year,
            ],
            [
                'user_id' => $event->userId,
                'total_tasks_count' => 0,
                'todo_tasks_count' => 0,
                'in_progress_tasks_count' => 0,
                'completed_tasks_count' => 0,
                'missed_tasks_count' => 0,
            ]
        );
    }

    public function failed(StudyPlanCreated $event, \Throwable $exception): void
    {
        Log::channel('errors')->error('Failed to create yearly study plan stats', [
            'user_id' => $event->userId,
            'study_plan_id' => $event->studyPlanId,
            'start_date' => $event->startDate,
            'error' => $exception->getMessage(),
        ]);
    }
}
