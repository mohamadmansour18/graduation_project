<?php

namespace App\Repositories\Notification;

use App\Enums\TaskStatus;
use App\Models\ScheduledNotificationDelivery;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudyPlanScheduledNotificationRepository
{
    private const array ACTIVE_TASK_STATUSES = [
        TaskStatus::TODO->value,
        TaskStatus::In_Progress->value
    ];

    public function reserveDelivery(int $userId, string $deliveryKey, string $notificationType, CarbonImmutable $deliverAt, array $context = [],): bool
    {
        $inserted = ScheduledNotificationDelivery::query()->insertOrIgnore([
            'user_id' => $userId,
            'delivery_key' => $deliveryKey,
            'notification_type' => $notificationType,
            'deliver_at' => $deliverAt->toDateTimeString(),
            'context' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $inserted === 1;
    }

    public function markAsDispatched(string $deliveryKey): void
    {
        ScheduledNotificationDelivery::query()
            ->where('delivery_key', $deliveryKey)
            ->whereNull('dispatched_at')
            ->update([
                'dispatched_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function findTaskStartOccurrencesDueBetween(CarbonImmutable $from, CarbonImmutable $to, int $limit = 500,): Collection
    {
        return DB::table('study_task_occurrence as occurrence')
            ->join('study_task as task', 'task.id', '=', 'occurrence.study_task_id')
            ->join('study_plan as plan', 'plan.id', '=', 'occurrence.study_plan_id')
            ->whereIn('task.status', self::ACTIVE_TASK_STATUSES)
            ->whereRaw(
                'TIMESTAMP(occurrence.occurrence_date, occurrence.scheduled_start_time) >= ?',
                [$from->toDateTimeString()]
            )
            ->whereRaw(
                'TIMESTAMP(occurrence.occurrence_date, occurrence.scheduled_start_time) < ?',
                [$to->toDateTimeString()]
            )
            ->where('task.status', '!=', TaskStatus::Completed->value)
            ->where('task.status', '!=', TaskStatus::Missed->value)
            ->orderBy('occurrence.occurrence_date')
            ->orderBy('occurrence.scheduled_start_time')
            ->limit($limit)
            ->get([
                'occurrence.id as occurrence_id',
                'occurrence.study_task_id as task_id',
                'occurrence.study_plan_id as study_plan_id',
                'occurrence.occurrence_date',
                'occurrence.scheduled_start_time',
                'occurrence.scheduled_end_time',

                'task.title as task_title',
                'task.description as task_description',
                'task.status as task_status',

                'plan.user_id',
                'plan.title as study_plan_title',
                'plan.is_default',
            ]);
    }

    public function findTaskEndOccurrencesDueBetween(CarbonImmutable $from, CarbonImmutable $to, int $limit = 500,): Collection
    {
        return DB::table('study_task_occurrence as occurrence')
            ->join('study_task as task', 'task.id', '=', 'occurrence.study_task_id')
            ->join('study_plan as plan', 'plan.id', '=', 'occurrence.study_plan_id')
            ->whereIn('task.status', self::ACTIVE_TASK_STATUSES)
            ->whereRaw(
                'TIMESTAMP(occurrence.occurrence_date, occurrence.scheduled_end_time) >= ?',
                [$from->toDateTimeString()]
            )
            ->whereRaw(
                'TIMESTAMP(occurrence.occurrence_date, occurrence.scheduled_end_time) < ?',
                [$to->toDateTimeString()]
            )
            ->orderBy('occurrence.occurrence_date')
            ->orderBy('occurrence.scheduled_end_time')
            ->limit($limit)
            ->get([
                'occurrence.id as occurrence_id',
                'occurrence.study_task_id as task_id',
                'occurrence.study_plan_id as study_plan_id',
                'occurrence.occurrence_date',
                'occurrence.scheduled_start_time',
                'occurrence.scheduled_end_time',

                'task.title as task_title',
                'task.description as task_description',
                'task.status as task_status',

                'plan.user_id',
                'plan.title as study_plan_title',
                'plan.is_default',
            ]);
    }

    public function findUsersWhoHaveTasksToday(CarbonImmutable $today): Collection
    {
        return DB::table('study_task_occurrence as occurrence')
            ->join('study_task as task', 'task.id', '=', 'occurrence.study_task_id')
            ->join('study_plan as plan', 'plan.id', '=', 'occurrence.study_plan_id')
            ->whereDate('occurrence.occurrence_date', $today->toDateString())
            ->whereIn('task.status', self::ACTIVE_TASK_STATUSES)
            ->groupBy('plan.user_id')
            ->get([
                'plan.user_id',
                DB::raw('COUNT(*) as tasks_count'),
            ]);
    }

    public function findDueTasksToMarkAsMissed(CarbonImmutable $now, int $limit = 500,): Collection
    {
        return DB::table('study_task as task')
            ->join('study_plan as plan', 'plan.id', '=', 'task.study_plan_id')
            ->whereNotNull('task.deadline_at')
            ->where('task.deadline_at', '<=', $now->toDateTimeString())
            ->whereNull('task.missed_at')
            ->where('task.status', '!=', TaskStatus::Completed->value)
            ->where('task.status', '!=', TaskStatus::Missed->value)
            ->orderBy('task.deadline_at')
            ->limit($limit)
            ->get([
                'task.id as task_id',
                'task.study_plan_id',
                'task.title as task_title',
                'task.description as task_description',
                'task.status as task_status',
                'task.deadline_at',

                'plan.user_id',
                'plan.title as study_plan_title',
                'plan.is_default',
            ]);
    }

    public function markTaskAsMissedIfStillNotCompleted(
        int $taskId,
        CarbonImmutable $missedAt,
    ): ?object {
        return DB::transaction(function () use ($taskId, $missedAt) {
            $task = DB::table('study_task')
                ->where('id', $taskId)
                ->lockForUpdate()
                ->first();

            if (! $task) {
                return null;
            }

            if ($task->status === TaskStatus::Completed->value) {
                return null;
            }

            if ($task->status === TaskStatus::Missed->value || ! is_null($task->missed_at)) {
                return null;
            }

            $plan = DB::table('study_plan')
                ->where('id', $task->study_plan_id)
                ->lockForUpdate()
                ->first();

            if (! $plan) {
                return null;
            }

            DB::table('study_task')
                ->where('id', $task->id)
                ->update([
                    'status' => TaskStatus::Missed->value,
                    'missed_at' => $missedAt->toDateTimeString(),
                    'updated_at' => now(),
                ]);

            DB::table('study_plan')
                ->where('id', $task->study_plan_id)
                ->update([
                    'missed_tasks_count' => DB::raw('missed_tasks_count + 1'),
                    'pending_tasks_count' => DB::raw('GREATEST(pending_tasks_count - 1, 0)'),
                    'updated_at' => now(),
                ]);

            return (object) [
                'task_id' => (int) $task->id,
                'study_plan_id' => (int) $task->study_plan_id,
                'task_title' => $task->title,
                'task_description' => $task->description,
                'old_status' => $task->status,
                'new_status' => 'missed',
                'deadline_at' => $task->deadline_at,
                'missed_at' => $missedAt->toDateTimeString(),

                'user_id' => (int) $plan->user_id,
                'study_plan_title' => $plan->title,
                'is_default' => (bool) $plan->is_default,
            ];
        });
    }
}
