<?php

namespace App\Services\StudyPlans;

use App\Enums\RepeatPattern;
use App\Enums\TaskStatus;
use App\Exceptions\Api\StudyPlanException;
use App\Models\StudyTask;
use App\Repositories\StudyPlans\StudyTaskRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudyTaskService
{
    public function __construct(
        private readonly StudyTaskRepository $studyTaskRepository,
    ) {}

    public function createTask(int $userId, int $studyPlanId, array $data): string
    {
        return DB::transaction(function () use ($userId, $studyPlanId, $data) {
            $plan = $this->studyTaskRepository->findPlanForUserForUpdate($userId, $studyPlanId);

            if (! $plan) {
                throw StudyPlanException::planNotFound();
            }


            if (! $this->studyTaskRepository->planSubjectBelongsToPlan(
                studyPlanId: $plan->id,
                studyPlanSubjectId: (int) $data['study_plan_subject_id']
            )) {
                throw StudyPlanException::subjectDoesNotBelongToPlan();
            }

            $baseStartDate = Carbon::parse($data['start_date'])->startOfDay();
            $baseEndDate = Carbon::parse($data['end_date'])->startOfDay();

            $this->ensureTaskInsidePlan(
                plan: $plan,
                taskStartDate: $baseStartDate,
                taskEndDate: $baseEndDate
            );

            $durationSeconds = ((int) $data['duration_minutes']) * 60;
            $dailyLimitSeconds = ((int) $plan->daily_study_minutes) * 60;

            $instances = $this->buildTaskInstances(
                planEndDate: Carbon::parse($plan->end_date)->startOfDay(),
                baseStartDate: $baseStartDate,
                baseEndDate: $baseEndDate,
                repeatPattern: $data['repeat_pattern'] ?? RepeatPattern::None->value,
                repeatWeekday: $data['repeat_weekday'] ?? null,
            );

            $this->ensureDailyCapacity(
                studyPlanId: $plan->id,
                instances: $instances['items'],
                durationSeconds: $durationSeconds,
                dailyLimitSeconds: $dailyLimitSeconds,
                recurrenceEnabled: ($data['repeat_pattern'] ?? RepeatPattern::None->value) !== RepeatPattern::None->value
            );

            $taskGroupUuid = (string) Str::uuid();
            $createdTasksCount = 0;
            $now = now();

            foreach ($instances['items'] as $instance) {
                $task = $this->studyTaskRepository->createTask([
                    'study_plan_id' => $plan->id,
                    'study_plan_subject_id' => (int) $data['study_plan_subject_id'],
                    'task_group_uuid' => $taskGroupUuid,

                    'title' => $data['title'],
                    'description' => $data['description'],

                    'start_date' => $instance['start_date'],
                    'end_date' => $instance['end_date'],
                    'start_time' => $data['start_time'],

                    'duration_seconds_per_day' => $durationSeconds,

                    'deadline_at' => $this->buildDeadlineAt(
                        endDate: $instance['end_date'],
                        startTime: $data['start_time'],
                        durationSeconds: $durationSeconds
                    ),

                    'reminder_offset_minutes' => $data['reminder_offset_minutes'] ?? null,
                    'priority' => $data['priority'],
                    'status' => TaskStatus::TODO->value,

                    'completed_at' => null,
                    'missed_at' => null,

                    'repeat_pattern' => $data['repeat_pattern'] ?? RepeatPattern::None->value,
                    'recurrence_end_date' => $instances['last_generated_end_date'],
                ]);

                $this->studyTaskRepository->createOccurrences(
                    $this->buildOccurrenceRows(
                        taskId: $task->id,
                        studyPlanId: $plan->id,
                        startDate: Carbon::parse($instance['start_date']),
                        endDate: Carbon::parse($instance['end_date']),
                        startTime: $data['start_time'],
                        durationSeconds: $durationSeconds,
                        now: $now
                    )
                );

                $this->studyTaskRepository->createSubtasks(
                    $this->buildSubtaskRows(
                        taskId: $task->id,
                        subtasks: $data['subtasks'] ?? [],
                        now: $now
                    )
                );

                $createdTasksCount++;
            }

            $this->studyTaskRepository->incrementPlanTaskCounters(
                studyPlanId: $plan->id,
                tasksCount: $createdTasksCount
            );

            if ($instances['was_truncated']) {
                return 'تم إنشاء المهمة بنجاح، وتم إيقاف التكرار عند آخر تاريخ مسموح ضمن حدود الخطة الدراسية';
            }

            return 'تم إنشاء المهمة بنجاح';
        });
    }

    private function ensureTaskInsidePlan($plan, Carbon $taskStartDate, Carbon $taskEndDate): void
    {
        $planStartDate = Carbon::parse($plan->start_date)->startOfDay();
        $planEndDate = Carbon::parse($plan->end_date)->startOfDay();

        if ($taskStartDate->lt($planStartDate) || $taskEndDate->gt($planEndDate)) {
            throw StudyPlanException::taskOutsidePlanDateRange();
        }
    }

    private function buildTaskInstances(Carbon $planEndDate, Carbon $baseStartDate, Carbon $baseEndDate, string $repeatPattern, ?int $repeatWeekday): array
    {
        $durationDays = $baseStartDate->diffInDays($baseEndDate);

        $items = [[
            'start_date' => $baseStartDate->toDateString(),
            'end_date' => $baseEndDate->toDateString(),
        ]];

        if ($repeatPattern === RepeatPattern::None->value) {
            return [
                'items' => $items,
                'was_truncated' => false,
                'last_generated_end_date' => $baseEndDate->toDateString(),
            ];
        }

        $intervalWeeks = match ($repeatPattern) {
            RepeatPattern::Weekly_1->value => 1,
            RepeatPattern::Weekly_2->value => 2,
            RepeatPattern::Weekly_3->value => 3,
            RepeatPattern::Weekly_4->value => 4,
            default => 1,
        };

        $firstRepeatStartDate = $baseStartDate->copy();

        while ((int) $firstRepeatStartDate->dayOfWeek !== (int) $repeatWeekday) {
            $firstRepeatStartDate->addDay();
        }

        if ($firstRepeatStartDate->isSameDay($baseStartDate)) {
            $nextStartDate = $firstRepeatStartDate->copy()->addWeeks($intervalWeeks);
        } else {
            $nextStartDate = $firstRepeatStartDate;
        }

        $wasTruncated = false;
        $lastGeneratedEndDate = $baseEndDate->copy();

        while (true) {
            $nextEndDate = $nextStartDate->copy()->addDays($durationDays);

            if ($nextEndDate->gt($planEndDate)) {
                $wasTruncated = true;
                break;
            }

            $items[] = [
                'start_date' => $nextStartDate->toDateString(),
                'end_date' => $nextEndDate->toDateString(),
            ];

            $lastGeneratedEndDate = $nextEndDate->copy();
            $nextStartDate->addWeeks($intervalWeeks);
        }

        return [
            'items' => $items,
            'was_truncated' => $wasTruncated,
            'last_generated_end_date' => $lastGeneratedEndDate->toDateString(),
        ];
    }

    private function ensureDailyCapacity(int $studyPlanId, array $instances, int $durationSeconds, int $dailyLimitSeconds, bool $recurrenceEnabled): void
    {
        $newDurationsByDate = [];

        foreach ($instances as $instance) {
            foreach (CarbonPeriod::create($instance['start_date'], $instance['end_date']) as $date) {
                $dateString = $date->toDateString();

                $newDurationsByDate[$dateString] = ($newDurationsByDate[$dateString] ?? 0) + $durationSeconds;
            }
        }

        $dates = array_keys($newDurationsByDate);

        $existingDurations = $this->studyTaskRepository->getDailyUsedSecondsForDates(
            studyPlanId: $studyPlanId,
            dates: $dates
        );

        foreach ($newDurationsByDate as $date => $newSeconds) {
            $existingSeconds = (int) ($existingDurations[$date] ?? 0);

            if (($existingSeconds + $newSeconds) > $dailyLimitSeconds) {
                if ($recurrenceEnabled) {
                    throw StudyPlanException::recurrenceBreaksDailyLimit($date);
                }

                throw StudyPlanException::dailyStudyLimitExceeded($date);
            }
        }
    }

    private function buildDeadlineAt(string $endDate, string $startTime, int $durationSeconds): string
    {
        return Carbon::parse($endDate . ' ' . $startTime)
            ->addSeconds($durationSeconds)
            ->toDateTimeString();
    }

    private function buildOccurrenceRows(int $taskId, int $studyPlanId, Carbon $startDate, Carbon $endDate, string $startTime, int $durationSeconds, $now): array
    {
        $rows = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $startDateTime = Carbon::parse($date->toDateString() . ' ' . $startTime);
            $endDateTime = $startDateTime->copy()->addSeconds($durationSeconds);

            $rows[] = [
                'study_task_id' => $taskId,
                'study_plan_id' => $studyPlanId,
                'occurrence_date' => $date->toDateString(),
                'scheduled_start_time' => $startDateTime->format('H:i:s'),
                'scheduled_end_time' => $endDateTime->format('H:i:s'),
                'duration_second' => $durationSeconds,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }

    private function buildSubtaskRows(int $taskId, array $subtasks, $now): array
    {
        return collect($subtasks)
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
    }

    public function updateTask(int $userId, int $studyPlanId, int $taskId, array $data): string
    {
        return DB::transaction(function () use ($userId, $studyPlanId, $taskId, $data) {
            $plan = $this->studyTaskRepository->findPlanForUserForUpdate($userId, $studyPlanId);

            if (! $plan) {
                throw StudyPlanException::planNotFound();
            }

            $task = $this->studyTaskRepository->findTaskForPlanForUpdate($studyPlanId, $taskId);

            if (! $task) {
                throw StudyPlanException::taskNotFound();
            }

            if (array_key_exists('study_plan_subject_id', $data)) {
                if (! $this->studyTaskRepository->planSubjectBelongsToPlan(
                    studyPlanId: $studyPlanId,
                    studyPlanSubjectId: (int) $data['study_plan_subject_id']
                )) {
                    throw StudyPlanException::subjectDoesNotBelongToPlan();
                }
            }

            if (array_key_exists('subtasks', $data)) {
                $this->ensureSubtasksBelongToTask($task->id, $data['subtasks']);
            }

            $merged = $this->mergeTaskData($task, $data);

            $temporalFieldsWereChanged = $this->temporalFieldsWereChanged($data);
            $recurrenceWasChanged = array_key_exists('repeat_pattern', $data);

            $subtasksTemplateForRepeatedTasks = [];

            if ($recurrenceWasChanged) {
                $subtasksTemplateForRepeatedTasks = array_key_exists('subtasks', $data)
                    ? $this->buildSubtasksTemplateFromRequest($data['subtasks'])
                    : $this->studyTaskRepository->getSubtasksTemplateForTask($task->id);
            }

            if ($temporalFieldsWereChanged || $recurrenceWasChanged) {
                $this->validateTemporalRules($plan, $merged);
            }

            $affectedTaskIds = [$task->id];

            if ($recurrenceWasChanged) {
                $futureGroupTaskIds = $this->studyTaskRepository->getFutureTaskIdsInSameGroup(
                    studyPlanId: $studyPlanId,
                    taskGroupUuid: $task->task_group_uuid,
                    fromStartDate: $merged['start_date'],
                    exceptTaskId: $task->id
                );

                $affectedTaskIds = array_values(array_unique([
                    ...$affectedTaskIds,
                    ...$futureGroupTaskIds,
                ]));
            }

            $instances = $this->buildTaskInstances2(
                planEndDate: Carbon::parse($plan->end_date)->startOfDay(),
                baseStartDate: Carbon::parse($merged['start_date'])->startOfDay(),
                baseEndDate: Carbon::parse($merged['end_date'])->startOfDay(),
                repeatPattern: $merged['repeat_pattern'],
                repeatWeekday: $merged['repeat_weekday'],
                recurrenceEnabled: $recurrenceWasChanged
            );

            if ($temporalFieldsWereChanged || $recurrenceWasChanged) {
                $this->ensureDailyCapacity2(
                    studyPlanId: $studyPlanId,
                    instances: $instances['items'],
                    durationSeconds: $merged['duration_seconds_per_day'],
                    dailyLimitSeconds: ((int) $plan->daily_study_minutes) * 60,
                    excludedTaskIds: $affectedTaskIds,
                    recurrenceEnabled: $merged['repeat_pattern'] !== 'بدون تكرار'
                );
            }

            if ($recurrenceWasChanged) {
                $idsToDelete = array_values(array_diff($affectedTaskIds, [$task->id]));
                $this->studyTaskRepository->deleteTasksByIds($idsToDelete);
            }

            $this->studyTaskRepository->updateTask($task, $this->buildTaskUpdatePayload($merged, $instances));

            if ($temporalFieldsWereChanged || $recurrenceWasChanged) {
                $this->studyTaskRepository->deleteOccurrencesForTask($task->id);

                $this->studyTaskRepository->createOccurrences(
                    $this->buildOccurrenceRows2(
                        taskId: $task->id,
                        studyPlanId: $studyPlanId,
                        startDate: Carbon::parse($merged['start_date']),
                        endDate: Carbon::parse($merged['end_date']),
                        startTime: $merged['start_time'],
                        durationSeconds: $merged['duration_seconds_per_day'],
                        now: now()
                    )
                );
            }

            if (array_key_exists('subtasks', $data)) {
                $this->studyTaskRepository->syncSubtasks($task->id, $data['subtasks']);
            }

            if ($recurrenceWasChanged && $merged['repeat_pattern'] !== 'بدون تكرار') {
                $this->createRepeatedTasksAfterRoot(
                    planId: $studyPlanId,
                    rootTask: $task->fresh(),
                    merged: $merged,
                    instances: array_slice($instances['items'], 1),
                    lastGeneratedEndDate: $instances['last_generated_end_date'],
                    subtasksTemplate: $subtasksTemplateForRepeatedTasks
                );
            }

            $this->studyTaskRepository->refreshPlanCounters($studyPlanId);

            if ($instances['was_truncated']) {
                return 'تم تعديل المهمة بنجاح، وتم إيقاف التكرار عند آخر تاريخ مسموح ضمن حدود الخطة الدراسية';
            }

            return 'تم تعديل المهمة بنجاح';
        });
    }

    public function deleteTask(int $userId, int $studyPlanId, int $taskId): void
    {
        DB::transaction(function () use ($userId, $studyPlanId, $taskId) {
            $plan = $this->studyTaskRepository->findPlanForUserForUpdate($userId, $studyPlanId);

            if (! $plan) {
                throw StudyPlanException::planNotFound();
            }

            $task = $this->studyTaskRepository->findTaskForPlanForUpdate($studyPlanId, $taskId);

            if (! $task) {
                throw StudyPlanException::taskNotFound();
            }

            $this->studyTaskRepository->forceDeleteTask($task);

            $this->studyTaskRepository->refreshPlanCounters($studyPlanId);
        });
    }

    private function mergeTaskData($task, array $data): array
    {
        $durationSeconds = array_key_exists('duration_minutes', $data)
            ? ((int) $data['duration_minutes']) * 60
            : (int) $task->duration_seconds_per_day;

        return [
            'title' => $data['title'] ?? $task->title,
            'description' => $data['description'] ?? $task->description,
            'study_plan_subject_id' => $data['study_plan_subject_id'] ?? $task->study_plan_subject_id,

            'start_date' => array_key_exists('start_date', $data)
                ? $data['start_date']
                : Carbon::parse($task->start_date)->toDateString(),

            'end_date' => array_key_exists('end_date', $data)
                ? $data['end_date']
                : Carbon::parse($task->end_date)->toDateString(),

            'start_time' => array_key_exists('start_time', $data)
                ? $data['start_time']
                : substr((string) $task->start_time, 0, 5),

            'duration_seconds_per_day' => $durationSeconds,

            'priority' => $data['priority'] ?? $task->priority,

            'repeat_pattern' => array_key_exists('repeat_pattern', $data)
                ? ($data['repeat_pattern'] ?? 'بدون تكرار')
                : ($task->repeat_pattern ?? 'بدون تكرار'),

            'repeat_weekday' => $data['repeat_weekday'] ?? null,

            'reminder_offset_minutes' => array_key_exists('reminder_offset_minutes', $data)
                ? $data['reminder_offset_minutes']
                : $task->reminder_offset_minutes,
        ];
    }

    private function temporalFieldsWereChanged(array $data): bool
    {
        return array_key_exists('start_date', $data)
            || array_key_exists('end_date', $data)
            || array_key_exists('start_time', $data)
            || array_key_exists('duration_minutes', $data);
    }

    private function validateTemporalRules($plan, array $merged): void
    {
        $startDate = Carbon::parse($merged['start_date'])->startOfDay();
        $endDate = Carbon::parse($merged['end_date'])->startOfDay();

        if ($endDate->lt($startDate)) {
            throw StudyPlanException::invalidDateRange();
        }

        if ($startDate->lt(today())) {
            throw StudyPlanException::taskStartDateCannotBePast();
        }

        if ($startDate->diffInDays($endDate) > 7) {
            throw StudyPlanException::taskDurationRangeInvalid();
        }

        $planStartDate = Carbon::parse($plan->start_date)->startOfDay();
        $planEndDate = Carbon::parse($plan->end_date)->startOfDay();

        if ($startDate->lt($planStartDate) || $endDate->gt($planEndDate)) {
            throw StudyPlanException::taskOutsidePlanDateRange();
        }

        $startDateTime = Carbon::parse($merged['start_date'] . ' ' . $merged['start_time']);
        $endDateTime = $startDateTime->copy()->addSeconds($merged['duration_seconds_per_day']);

        if (! $startDateTime->isSameDay($endDateTime)) {
            throw StudyPlanException::taskCrossesDayBoundary();
        }
    }

    private function buildTaskInstances2(Carbon $planEndDate, Carbon $baseStartDate, Carbon $baseEndDate, string $repeatPattern, ?int $repeatWeekday, bool $recurrenceEnabled): array
    {
        $items = [[
            'start_date' => $baseStartDate->toDateString(),
            'end_date' => $baseEndDate->toDateString(),
        ]];

        if (! $recurrenceEnabled || $repeatPattern === 'بدون تكرار') {
            return [
                'items' => $items,
                'was_truncated' => false,
                'last_generated_end_date' => $baseEndDate->toDateString(),
            ];
        }

        $intervalWeeks = match ($repeatPattern) {
            'كل أسبوع' => 1,
            'كل أسبوعين' => 2,
            'كل 3 أسابيع' => 3,
            'كل 4 أسابيع' => 4,
            default => 1,
        };

        $durationDays = $baseStartDate->diffInDays($baseEndDate);

        $nextStartDate = $baseStartDate->copy();

        while ((int) $nextStartDate->dayOfWeek !== (int) $repeatWeekday) {
            $nextStartDate->addDay();
        }

        if ($nextStartDate->isSameDay($baseStartDate)) {
            $nextStartDate->addWeeks($intervalWeeks);
        }

        $wasTruncated = false;
        $lastGeneratedEndDate = $baseEndDate->copy();

        while (true) {
            $nextEndDate = $nextStartDate->copy()->addDays($durationDays);

            if ($nextEndDate->gt($planEndDate)) {
                $wasTruncated = true;
                break;
            }

            $items[] = [
                'start_date' => $nextStartDate->toDateString(),
                'end_date' => $nextEndDate->toDateString(),
            ];

            $lastGeneratedEndDate = $nextEndDate->copy();
            $nextStartDate->addWeeks($intervalWeeks);
        }

        return [
            'items' => $items,
            'was_truncated' => $wasTruncated,
            'last_generated_end_date' => $lastGeneratedEndDate->toDateString(),
        ];
    }

    private function ensureDailyCapacity2(int $studyPlanId, array $instances, int $durationSeconds, int $dailyLimitSeconds, array $excludedTaskIds, bool $recurrenceEnabled): void
    {
        $newDurationsByDate = [];

        foreach ($instances as $instance) {
            foreach (CarbonPeriod::create($instance['start_date'], $instance['end_date']) as $date) {
                $dateString = $date->toDateString();
                $newDurationsByDate[$dateString] = ($newDurationsByDate[$dateString] ?? 0) + $durationSeconds;
            }
        }

        $existingDurations = $this->studyTaskRepository->getDailyUsedSecondsForDatesExcludingTasks(
            studyPlanId: $studyPlanId,
            dates: array_keys($newDurationsByDate),
            excludedTaskIds: $excludedTaskIds
        );

        foreach ($newDurationsByDate as $date => $newSeconds) {
            $existingSeconds = (int) ($existingDurations[$date] ?? 0);

            if (($existingSeconds + $newSeconds) > $dailyLimitSeconds) {
                if ($recurrenceEnabled) {
                    throw StudyPlanException::recurrenceBreaksDailyLimit($date);
                }

                throw StudyPlanException::dailyStudyLimitExceeded($date);
            }
        }
    }

    private function buildTaskUpdatePayload(array $merged, array $instances): array
    {
        return [
            'title' => $merged['title'],
            'description' => $merged['description'],
            'study_plan_subject_id' => $merged['study_plan_subject_id'],
            'start_date' => $merged['start_date'],
            'end_date' => $merged['end_date'],
            'start_time' => $merged['start_time'],
            'duration_seconds_per_day' => $merged['duration_seconds_per_day'],
            'deadline_at' => $this->buildDeadlineAt(
                endDate: $merged['end_date'],
                startTime: $merged['start_time'],
                durationSeconds: $merged['duration_seconds_per_day']
            ),
            'reminder_offset_minutes' => $merged['reminder_offset_minutes'],
            'priority' => $merged['priority'],
            'repeat_pattern' => $merged['repeat_pattern'],
            'recurrence_end_date' => $instances['last_generated_end_date'],
        ];
    }

    private function createRepeatedTasksAfterRoot(int $planId, $rootTask, array $merged, array $instances , string $lastGeneratedEndDate , array $subtasksTemplate): void
    {
        foreach ($instances as $instance) {
            $newTask = $this->studyTaskRepository->createTask([
                'study_plan_id' => $planId,
                'study_plan_subject_id' => $merged['study_plan_subject_id'],
                'task_group_uuid' => $rootTask->task_group_uuid,

                'title' => $merged['title'],
                'description' => $merged['description'],

                'start_date' => $instance['start_date'],
                'end_date' => $instance['end_date'],
                'start_time' => $merged['start_time'],
                'duration_seconds_per_day' => $merged['duration_seconds_per_day'],

                'deadline_at' => $this->buildDeadlineAt(
                    endDate: $instance['end_date'],
                    startTime: $merged['start_time'],
                    durationSeconds: $merged['duration_seconds_per_day']
                ),

                'reminder_offset_minutes' => $merged['reminder_offset_minutes'],
                'priority' => $merged['priority'],
                'status' => TaskStatus::TODO->value,
                'completed_at' => null,
                'missed_at' => null,
                'repeat_pattern' => $merged['repeat_pattern'],
                'recurrence_end_date' => $lastGeneratedEndDate,
            ]);

            $this->studyTaskRepository->createOccurrences(
                $this->buildOccurrenceRows2(
                    taskId: $newTask->id,
                    studyPlanId: $planId,
                    startDate: Carbon::parse($instance['start_date']),
                    endDate: Carbon::parse($instance['end_date']),
                    startTime: $merged['start_time'],
                    durationSeconds: $merged['duration_seconds_per_day'],
                    now: now()
                )
            );

            $this->studyTaskRepository->createSubtasksForTaskFromTemplate(
                taskId: $newTask->id,
                subtasksTemplate: $subtasksTemplate
            );
        }
    }

    private function buildOccurrenceRows2(int $taskId, int $studyPlanId, Carbon $startDate, Carbon $endDate, string $startTime, int $durationSeconds, $now): array
    {
        $rows = [];
        $seenDates = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $dateString = $date->toDateString();

            if (isset($seenDates[$dateString])) {
                continue;
            }

            $seenDates[$dateString] = true;

            $startDateTime = Carbon::parse($dateString . ' ' . $startTime);
            $endDateTime = $startDateTime->copy()->addSeconds($durationSeconds);

            $rows[] = [
                'study_task_id' => $taskId,
                'study_plan_id' => $studyPlanId,
                'occurrence_date' => $dateString,
                'scheduled_start_time' => $startDateTime->format('H:i:s'),
                'scheduled_end_time' => $endDateTime->format('H:i:s'),
                'duration_second' => $durationSeconds,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }

    private function ensureSubtasksBelongToTask(int $taskId, array $subtasks): void
    {
        $ids = collect($subtasks)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($ids)) {
            return;
        }

        $existingIds = $this->studyTaskRepository->getSubtaskIdsForTask($taskId);

        foreach ($ids as $id) {
            if (! in_array($id, $existingIds, true)) {
                throw StudyPlanException::subtaskDoesNotBelongToTask();
            }
        }
    }

    private function buildSubtasksTemplateFromRequest(array $subtasks): array
    {
        return collect($subtasks)
            ->values()
            ->map(fn (array $subtask, int $index) => [
                'title' => trim((string) $subtask['title']),
                'position' => $index + 1,
            ])
            ->all();
    }

    public function getTaskDetailsForEditing(int $userId, int $studyPlanId, int $taskId): StudyTask
    {
        $task = $this->studyTaskRepository->findTaskDetailsForUser(
            userId: $userId,
            studyPlanId: $studyPlanId,
            taskId: $taskId
        );

        if (! $task) {
            throw StudyPlanException::taskNotFound();
        }

        $task->task_number = $this->studyTaskRepository->getTaskNumberInsidePlan(
            studyPlanId: $studyPlanId,
            task: $task
        );

        return $task;
    }
}
