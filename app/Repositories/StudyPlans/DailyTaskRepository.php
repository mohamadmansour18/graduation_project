<?php

namespace App\Repositories\StudyPlans;

use App\Models\StudyPlan;
use App\Models\StudyTaskOccurrence;
use Illuminate\Support\Facades\DB;

class DailyTaskRepository
{
    public function findDefaultForUser(int $userId): ?StudyPlan
    {
        return StudyPlan::query()
            ->select([
                'id',
                'user_id',
                'title',
                'emoji',
                'start_date',
                'end_date',
                'daily_study_minutes',
                'is_default',
                'subjects_count',
                'tasks_count',
                'completed_tasks_count',
                'missed_tasks_count',
                'pending_tasks_count',
            ])
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->first();
    }

    public function getRangeDailySummaries(int $studyPlanId, string $rangeStart, string $rangeEnd): \Illuminate\Support\Collection
    {
        return StudyTaskOccurrence::query()
            ->from('study_task_occurrence as occurrence')
            ->join('study_task as task', 'task.id', '=', 'occurrence.study_task_id')
            ->where('occurrence.study_plan_id', $studyPlanId)
            ->whereBetween('occurrence.occurrence_date', [$rangeStart, $rangeEnd])
            ->groupBy('occurrence.occurrence_date')
            ->orderBy('occurrence.occurrence_date')
            ->get([
                'occurrence.occurrence_date',
                DB::raw('COUNT(*) as total_tasks'),
                DB::raw("SUM(CASE WHEN task.status = 'تم انجازها' THEN 1 ELSE 0 END) as completed_tasks"),
            ])
            ->keyBy(fn ($item) => (string) $item->occurrence_date);
    }

    public function getTasksForDate(int $studyPlanId, string $selectedDate): array|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_StudyTaskOccurrence_C
    {
        $subtaskCounts = DB::table('study_task_subtask')
            ->select([
                'study_task_id',
                DB::raw('COUNT(*) as subtasks_total_count'),
                DB::raw('SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_subtasks_count'),
            ])
            ->groupBy('study_task_id');

        return StudyTaskOccurrence::query()
            ->from('study_task_occurrence as occurrence')
            ->join('study_task as task', 'task.id', '=', 'occurrence.study_task_id')
            ->leftJoinSub($subtaskCounts, 'subtask_counts', function ($join) {
                $join->on('subtask_counts.study_task_id', '=', 'task.id');
            })
            ->where('occurrence.study_plan_id', $studyPlanId)
            ->whereDate('occurrence.occurrence_date', $selectedDate)
            ->orderBy('occurrence.scheduled_start_time')
            ->orderBy('occurrence.id')
            ->get([
                'occurrence.id as occurrence_id',
                'occurrence.occurrence_date',
                'occurrence.scheduled_start_time',
                'occurrence.scheduled_end_time',
                'occurrence.duration_second',

                'task.id',
                'task.title',
                'task.status',
                'task.priority',

                DB::raw('COALESCE(subtask_counts.subtasks_total_count, 0) as subtasks_total_count'),
                DB::raw('COALESCE(subtask_counts.completed_subtasks_count, 0) as completed_subtasks_count'),
            ]);
    }
}
