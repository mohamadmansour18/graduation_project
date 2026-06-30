<?php

namespace App\Repositories\StudyPlans;

use App\Models\StudyPlan;
use App\Models\StudyPlanSubject;
use App\Models\StudyTask;
use App\Models\StudyTaskOccurrence;
use App\Models\StudyTaskSubtask;
use Illuminate\Support\Facades\DB;

class  StudyTaskRepository
{
    public function findPlanForUserForUpdate(int $userId, int $studyPlanId): ?StudyPlan
    {
        return StudyPlan::query()
            ->where('id', $studyPlanId)
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();
    }

    public function planSubjectBelongsToPlan(int $studyPlanId, int $studyPlanSubjectId): bool
    {
        return StudyPlanSubject::query()
            ->where('id', $studyPlanSubjectId)
            ->where('study_plan_id', $studyPlanId)
            ->exists();
    }

    public function getDailyUsedSecondsForDates(int $studyPlanId, array $dates): \Illuminate\Support\Collection
    {
        if (empty($dates)) {
            return collect();
        }

        return StudyTaskOccurrence::query()
            ->select([
                'occurrence_date',
                DB::raw('SUM(duration_second) as total_seconds'),
            ])
            ->where('study_plan_id', $studyPlanId)
            ->whereIn('occurrence_date', $dates)
            ->groupBy('occurrence_date')
            ->pluck('total_seconds', 'occurrence_date');
    }

    public function createTask(array $data): StudyTask
    {
        return StudyTask::query()->create($data);
    }

    public function createOccurrences(array $rows): void
    {
        if (! empty($rows)) {
            StudyTaskOccurrence::query()->insert($rows);
        }
    }

    public function createSubtasks(array $rows): void
    {
        if (! empty($rows)) {
            StudyTaskSubtask::query()->insert($rows);
        }
    }

    public function incrementPlanTaskCounters(int $studyPlanId, int $tasksCount): void
    {
        StudyPlan::query()
            ->where('id', $studyPlanId)
            ->update([
                'tasks_count' => DB::raw("tasks_count + {$tasksCount}"),
                'pending_tasks_count' => DB::raw("pending_tasks_count + {$tasksCount}"),
                'updated_at' => now(),
            ]);
    }

    public function findTaskForPlanForUpdate(int $studyPlanId, int $taskId): ?StudyTask
    {
        return StudyTask::query()
            ->where('id', $taskId)
            ->where('study_plan_id', $studyPlanId)
            ->lockForUpdate()
            ->first();
    }

    public function getFutureTaskIdsInSameGroup(int $studyPlanId, string $taskGroupUuid, string $fromStartDate, int $exceptTaskId): array
    {
        return StudyTask::query()
            ->where('study_plan_id', $studyPlanId)
            ->where('task_group_uuid', $taskGroupUuid)
            ->where('id', '!=', $exceptTaskId)
            ->whereDate('start_date', '>=', $fromStartDate)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function getDailyUsedSecondsForDatesExcludingTasks(int $studyPlanId, array $dates, array $excludedTaskIds = []): \Illuminate\Support\Collection
    {
        if (empty($dates)) {
            return collect();
        }

        return StudyTaskOccurrence::query()
            ->select([
                'occurrence_date',
                DB::raw('SUM(duration_second) as total_seconds'),
            ])
            ->where('study_plan_id', $studyPlanId)
            ->whereIn('occurrence_date', $dates)
            ->when(! empty($excludedTaskIds), function ($query) use ($excludedTaskIds) {
                $query->whereNotIn('study_task_id', $excludedTaskIds);
            })
            ->groupBy('occurrence_date')
            ->pluck('total_seconds', 'occurrence_date');
    }

    public function updateTask(StudyTask $task, array $data): void
    {
        $task->update($data);
    }

    public function deleteOccurrencesForTask(int $taskId): void
    {
        StudyTaskOccurrence::query()
            ->where('study_task_id', $taskId)
            ->delete();
    }

    public function deleteTasksByIds(array $taskIds): void
    {
        if (! empty($taskIds)) {
            StudyTask::query()
                ->whereIn('id', $taskIds)
                ->delete();
        }
    }

    public function forceDeleteTask(StudyTask $task): void
    {
        if (method_exists($task, 'forceDelete')) {
            $task->forceDelete();
            return;
        }

        $task->delete();
    }

    public function getSubtaskIdsForTask(int $taskId): array
    {
        return StudyTaskSubtask::query()
            ->where('study_task_id', $taskId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function syncSubtasks(int $taskId, array $subtasks): void
    {
        $now = now();

        $incomingExistingIds = collect($subtasks)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        StudyTaskSubtask::query()
            ->where('study_task_id', $taskId)
            ->when(! empty($incomingExistingIds), function ($query) use ($incomingExistingIds) {
                $query->whereNotIn('id', $incomingExistingIds);
            })
            ->delete();

        foreach (array_values($subtasks) as $index => $subtask) {
            $payload = [
                'title' => trim((string) $subtask['title']),
                'position' => $index + 1,
                'is_completed' => (bool) ($subtask['is_completed'] ?? false),
                'completed_at' => (bool) ($subtask['is_completed'] ?? false) ? $now : null,
                'updated_at' => $now,
                'created_at' => $now,
            ];

            if (! empty($subtask['id'])) {
                StudyTaskSubtask::query()
                    ->where('id', (int) $subtask['id'])
                    ->where('study_task_id', $taskId)
                    ->update($payload);

                continue;
            }

            StudyTaskSubtask::query()->create([
                'study_task_id' => $taskId,
                ...$payload,
            ]);
        }
    }

    public function refreshPlanCounters(int $studyPlanId): void
    {
        $counts = StudyTask::query()
            ->where('study_plan_id', $studyPlanId)
            ->select([
                DB::raw('COUNT(*) as total_tasks_count'),
                DB::raw("SUM(CASE WHEN status = 'للقيام' THEN 1 ELSE 0 END) as todo_tasks_count"),
                DB::raw("SUM(CASE WHEN status = 'قيد المعالجة' THEN 1 ELSE 0 END) as in_progress_tasks_count"),
                DB::raw("SUM(CASE WHEN status = 'تم انجازها' THEN 1 ELSE 0 END) as completed_tasks_count"),
                DB::raw("SUM(CASE WHEN status = 'فائتة' THEN 1 ELSE 0 END) as missed_tasks_count"),
            ])
            ->first();

        $pendingTasksCount = ((int) ($counts?->todo_tasks_count ?? 0))
            + ((int) ($counts?->in_progress_tasks_count ?? 0));

        StudyPlan::query()
            ->where('id', $studyPlanId)
            ->update([
                'tasks_count' => (int) ($counts?->total_tasks_count ?? 0),
                'completed_tasks_count' => (int) ($counts?->completed_tasks_count ?? 0),
                'missed_tasks_count' => (int) ($counts?->missed_tasks_count ?? 0),
                'pending_tasks_count' => $pendingTasksCount,
                'updated_at' => now(),
            ]);
    }

    public function getSubtasksTemplateForTask(int $taskId): array
    {
        return StudyTaskSubtask::query()
            ->where('study_task_id', $taskId)
            ->orderBy('position')
            ->get(['title', 'position'])
            ->map(fn ($subtask) => [
                'title' => $subtask->title,
                'position' => (int) $subtask->position,
            ])
            ->all();
    }

    public function createSubtasksForTaskFromTemplate(int $taskId, array $subtasksTemplate): void
    {
        if (empty($subtasksTemplate)) {
            return;
        }

        $now = now();

        $rows = collect($subtasksTemplate)
            ->values()
            ->map(fn (array $subtask, int $index) => [
                'study_task_id' => $taskId,
                'title' => trim((string) $subtask['title']),
                'position' => $index + 1,

                'is_completed' => false,
                'completed_at' => null,

                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        StudyTaskSubtask::query()->insert($rows);
    }

    public function findTaskDetailsForUser(int $userId, int $studyPlanId, int $taskId): ?StudyTask
    {
        return StudyTask::query()
            ->select([
                'id',
                'study_plan_id',
                'study_plan_subject_id',
                'task_group_uuid',
                'title',
                'description',
                'start_date',
                'end_date',
                'start_time',
                'duration_seconds_per_day',
                'deadline_at',
                'reminder_offset_minutes',
                'priority',
                'status',
                'completed_at',
                'missed_at',
                'repeat_pattern',
                'recurrence_end_date',
            ])
            ->where('id', $taskId)
            ->where('study_plan_id', $studyPlanId)
            ->whereHas('studyPlan', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with([
                'studyPlanSubject:id,study_plan_id,study_subject_id,slot_no',
                'studyPlanSubject.studySubject:id,name',
                'studyTaskSubtasks' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'study_task_id',
                            'title',
                            'position',
                            'is_completed',
                            'completed_at',
                        ])
                        ->orderBy('position')
                        ->orderBy('id');
                },
            ])
            ->withCount([
                'studyTaskSubtasks as subtasks_total_count',
                'studyTaskSubtasks as completed_subtasks_count' => function ($query) {
                    $query->where('is_completed', true);
                },
            ])
            ->first();
    }

    public function getTaskNumberInsidePlan(int $studyPlanId, StudyTask $task): int
    {
        $taskStartDate = $task->start_date instanceof \Carbon\CarbonInterface
            ? $task->start_date->toDateString()
            : (string) $task->start_date;

        $taskStartTime = substr((string) $task->start_time, 0, 8);

        return StudyTask::query()
            ->where('study_plan_id', $studyPlanId)
            ->where(function ($query) use ($task, $taskStartDate, $taskStartTime) {
                $query
                    ->whereDate('start_date', '<', $taskStartDate)
                    ->orWhere(function ($query) use ($taskStartDate, $taskStartTime) {
                        $query
                            ->whereDate('start_date', $taskStartDate)
                            ->where('start_time', '<', $taskStartTime);
                    })
                    ->orWhere(function ($query) use ($task, $taskStartDate, $taskStartTime) {
                        $query
                            ->whereDate('start_date', $taskStartDate)
                            ->where('start_time', $taskStartTime)
                            ->where('id', '<=', $task->id);
                    });
            })
            ->count();
    }

    public function updateTaskStatus(StudyTask $task, string $status, ?string $completedAt = null, ?string $missedAt = null): void
    {
        $task->update([
            'status' => $status,
            'completed_at' => $completedAt,
            'missed_at' => $missedAt,
        ]);
    }

    public function completeAllSubtasksForTask(int $taskId): void
    {
        StudyTaskSubtask::query()
            ->where('study_task_id', $taskId)
            ->where('is_completed', false)
            ->update([
                'is_completed' => true,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function unCompleteAllSubtasksForTask(int $taskId): void
    {
        StudyTaskSubtask::query()
            ->where('study_task_id', $taskId)
            ->where('is_completed', true)
            ->update([
                'is_completed' => false,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
}
