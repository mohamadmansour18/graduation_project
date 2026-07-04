<?php

namespace App\Services\StudyPlans;

use App\Enums\StudyTaskStatus;
use App\Enums\TaskStatus;
use App\Events\StudyPlanCreated;
use App\Events\StudyPlanDeleted;
use App\Exceptions\Api\StudyPlanException;
use App\Http\Resources\StudyPlanDetailsResource;
use App\Http\Resources\StudyPlanOverviewResource;
use App\Http\Resources\StudyPlanTasksGroupedResource;
use App\Repositories\StudyPlans\StudyPlanRepository;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudyPlanService
{
    public function __construct(
        private readonly StudyPlanRepository $studyPlanRepository,
    )
    {}

    public function createStudyPlan(int $userId, array $data, bool $isDefaultWasProvided): void
    {
        $subjectIds = array_values($data['subject_ids']);
        $isDefault = (bool) ($data['is_default'] ?? false);

        $allPlansCount = $this->studyPlanRepository->countAllUserPlans($userId);

        if ($allPlansCount === 0 && ! $isDefaultWasProvided) {
            throw StudyPlanException::defaultFlagRequiredForFirstPlan();
        }

        if ($allPlansCount === 0 && ! $isDefault) {
            throw StudyPlanException::firstPlanMustBeDefault();
        }

        $activeOrFuturePlansCount = $this->studyPlanRepository->countActiveOrFuturePlans($userId);

        if ($activeOrFuturePlansCount >= 5) {
            throw StudyPlanException::maxActiveOrFuturePlansReached();
        }

        $ownedSubjectsCount = $this->studyPlanRepository->countUserSubjectsByIds(
            userId: $userId,
            subjectIds: $subjectIds
        );

        if ($ownedSubjectsCount !== count($subjectIds)) {
            throw StudyPlanException::someSubjectsDoNotBelongToUser();
        }

        DB::transaction(function () use ($userId, $data, $subjectIds, $isDefault) {
            if ($isDefault) {
                $this->studyPlanRepository->unsetDefaultPlansForUser($userId);
            }

            $studyPlan = $this->studyPlanRepository->createPlan([
                'user_id' => $userId,
                'title' => $data['title'],
                'emoji' => $data['emoji'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],

                'daily_study_minutes' => ((int) $data['daily_study_hours']) * 60,

                'is_default' => $isDefault,
                'subjects_count' => count($subjectIds),

                'tasks_count' => 0,
                'completed_tasks_count' => 0,
                'missed_tasks_count' => 0,
                'pending_tasks_count' => 0,
            ]);

            $this->studyPlanRepository->attachSubjects(
                studyPlanId: $studyPlan->id,
                subjectIds: $subjectIds
            );

            DB::afterCommit(function () use ($studyPlan) {
                StudyPlanCreated::dispatch(
                    $studyPlan->user_id,
                    $studyPlan->id,
                    $studyPlan->start_date->toDateString()
                );
            });
        });
    }

    public function getUserPlansByTab(int $userId, string $tab): array
    {
        $plans = $this->studyPlanRepository->getUserPlansByTab(
            userId: $userId,
            tab: $tab
        );

        return [
            'plans_count' => $plans->count(),
            'plans' => StudyPlanOverviewResource::collection($plans),
        ];
    }

    public function getPlanDetails(int $userId, int $studyPlanId): StudyPlanDetailsResource
    {
        $plan = $this->studyPlanRepository->findPlanDetailsForUser($userId, $studyPlanId);

        if (! $plan) {
            throw StudyPlanException::planNotFound();
        }

        return new StudyPlanDetailsResource($plan);
    }

    public function getPlanTasks(int $userId, int $studyPlanId): StudyPlanTasksGroupedResource
    {
        if (! $this->studyPlanRepository->existsForUser($userId, $studyPlanId)) {
            throw StudyPlanException::planNotFound();
        }

        $now = now();

        $occurrences = $this->studyPlanRepository
            ->getPlanTaskOccurrencesGroupedData($studyPlanId)
            ->values();

        $tasks = $occurrences
            ->groupBy('id')
            ->map(function ($taskOccurrences) {
                $firstOccurrence = $taskOccurrences->first();
                $lastOccurrence = $taskOccurrences->last();

                $startDateTime = Carbon::parse(
                    $firstOccurrence->occurrence_date->toDateString() . ' ' . $firstOccurrence->scheduled_start_time
                );

                $endDateTime = Carbon::parse(
                    $lastOccurrence->occurrence_date->toDateString() . ' ' . $lastOccurrence->scheduled_end_time
                );

                $firstOccurrence->occurrence_ids = $taskOccurrences
                    ->pluck('occurrence_id')
                    ->values();

                $firstOccurrence->occurrences_count = $taskOccurrences->count();

                $firstOccurrence->start_date = $firstOccurrence->occurrence_date;
                $firstOccurrence->end_date = $lastOccurrence->occurrence_date;

                $firstOccurrence->range_start_datetime = $startDateTime;
                $firstOccurrence->range_end_datetime = $endDateTime;

                return $firstOccurrence;
            })
            ->values()
            ->sortBy('range_start_datetime')
            ->values()
            ->map(function ($task, int $index) {
                $task->task_number = $index + 1;

                return $task;
            });

        $completed = $tasks
            ->where('status', TaskStatus::Completed->value)
            ->values();

        $old = $tasks
            ->filter(function ($item) use ($now) {
                if ($item->status === TaskStatus::Completed->value) {
                    return false;
                }

                return $item->range_end_datetime->lt($now);
            })
            ->values();

        $upcoming = $tasks
            ->filter(function ($item) use ($now) {
                if ($item->status === TaskStatus::Completed->value) {
                    return false;
                }

                return $item->range_end_datetime->gte($now);
            })
            ->values();

        return new StudyPlanTasksGroupedResource([
            'old' => $old,
            'upcoming' => $upcoming,
            'completed' => $completed,
        ]);
    }

    public function updateStudyPlan(int $userId, int $studyPlanId, array $data): void
    {
        DB::transaction(function () use ($userId, $studyPlanId, $data) {
            $studyPlan = $this->studyPlanRepository->findForUserForUpdate($userId, $studyPlanId);

            if (! $studyPlan) {
                throw StudyPlanException::planNotFound();
            }

            $updateData = [];

            if (array_key_exists('title', $data)) {
                $updateData['title'] = $data['title'];
            }

            if (array_key_exists('emoji', $data)) {
                $updateData['emoji'] = $data['emoji'];
            }

            $this->validateAndFillDatesIfProvided($studyPlan, $data, $updateData);
            $this->validateAndFillDailyHoursIfProvided($studyPlan, $data, $updateData);
            $this->handleDefaultFlagIfProvided($userId, $studyPlan, $data, $updateData);
            $this->handleSubjectsIfProvided($userId, $studyPlan, $data, $updateData);

            if (! empty($updateData)) {
                $this->studyPlanRepository->updatePlan($studyPlan, $updateData);
            }
        });
    }

    public function deleteStudyPlan(int $userId, int $studyPlanId): void
    {
        DB::transaction(function () use ($userId, $studyPlanId) {
            $studyPlan = $this->studyPlanRepository->findForUserForUpdate($userId, $studyPlanId);

            if (! $studyPlan) {
                throw StudyPlanException::planNotFound();
            }

            $deletedPlanWasDefault = (bool) $studyPlan->is_default;

            if ($deletedPlanWasDefault) {
                $replacementPlan = $this->studyPlanRepository->findReplacementDefaultPlan(
                    userId: $userId,
                    excludedStudyPlanId: $studyPlanId
                );

                if ($replacementPlan) {
                    $replacementPlan->update(['is_default' => true]);
                }
            }

            $this->studyPlanRepository->forceDeletePlan($studyPlan);

            DB::afterCommit(function () use ($userId, $studyPlanId) {
                StudyPlanDeleted::dispatch(
                    $userId,
                    $studyPlanId
                );
            });
        });
    }

    private function validateAndFillDatesIfProvided($studyPlan, array $data, array &$updateData): void
    {
        if (! array_key_exists('start_date', $data) && ! array_key_exists('end_date', $data)) {
            return;
        }

        $newStartDate = array_key_exists('start_date', $data)
            ? Carbon::parse($data['start_date'])->startOfDay()
            : Carbon::parse($studyPlan->start_date)->startOfDay();

        $newEndDate = array_key_exists('end_date', $data)
            ? Carbon::parse($data['end_date'])->startOfDay()
            : Carbon::parse($studyPlan->end_date)->startOfDay();

        if ($newStartDate->lt(today())) {
            throw StudyPlanException::startDateCannotBePast();
        }

        if ($newEndDate->lte($newStartDate)) {
            throw StudyPlanException::invalidPlanDateRange();
        }

        $durationDays = $newStartDate->diffInDays($newEndDate);

        if ($durationDays < 1 || $durationDays > 365) {
            throw StudyPlanException::invalidPlanDuration();
        }

        if ($this->studyPlanRepository->hasTasksOutsideDateRange(
            studyPlanId: $studyPlan->id,
            newStartDate: $newStartDate->toDateString(),
            newEndDate: $newEndDate->toDateString()
        )) {
            throw StudyPlanException::tasksOutsideNewPlanDateRange();
        }

        $updateData['start_date'] = $newStartDate->toDateString();
        $updateData['end_date'] = $newEndDate->toDateString();
    }

    private function validateAndFillDailyHoursIfProvided($studyPlan, array $data, array &$updateData): void
    {
        if (! array_key_exists('daily_study_hours', $data)) {
            return;
        }

        $newDailyStudySeconds = ((int) $data['daily_study_hours']) * 60 * 60;
        $maxDailyScheduledSeconds = $this->studyPlanRepository->maxDailyScheduledSeconds($studyPlan->id);

        if ($maxDailyScheduledSeconds > $newDailyStudySeconds) {
            throw StudyPlanException::dailyStudyHoursBreakExistingTasks();
        }

        $updateData['daily_study_minutes'] = ((int) $data['daily_study_hours']) * 60;
    }

    private function handleDefaultFlagIfProvided(int $userId, $studyPlan, array $data, array &$updateData): void
    {
        if (! array_key_exists('is_default', $data)) {
            return;
        }

        $requestedDefaultValue = (bool) $data['is_default'];

        if ($requestedDefaultValue === true) {
            if ((bool) $studyPlan->is_default) {
                throw StudyPlanException::alreadyDefaultPlan();
            }

            $this->studyPlanRepository->unsetDefaultPlansForUser($userId);
            $updateData['is_default'] = true;

            return;
        }

        if ($requestedDefaultValue === false && (bool) $studyPlan->is_default) {
            throw StudyPlanException::cannotUnsetDefaultPlan();
        }
    }

    private function handleSubjectsIfProvided(int $userId, $studyPlan, array $data, array &$updateData): void
    {
        if (! array_key_exists('subject_ids', $data)) {
            return;
        }

        $subjectIds = array_values($data['subject_ids']);

        $ownedSubjectsCount = $this->studyPlanRepository->countUserSubjectsByIds(
            userId: $userId,
            subjectIds: $subjectIds
        );

        if ($ownedSubjectsCount !== count($subjectIds)) {
            throw StudyPlanException::someSubjectsDoNotBelongToUser2();
        }

        $currentPlanSubjects = $this->studyPlanRepository->getPlanSubjects($studyPlan->id);

        $currentSubjectIds = $currentPlanSubjects
            ->pluck('study_subject_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $removedSubjectIds = array_diff($currentSubjectIds, $subjectIds);

        if (! empty($removedSubjectIds)) {
            $removedPlanSubjectIds = $currentPlanSubjects
                ->whereIn('study_subject_id', $removedSubjectIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($this->studyPlanRepository->hasTasksForPlanSubjectIds($removedPlanSubjectIds)) {
                throw StudyPlanException::cannotRemoveSubjectHasTasks();
            }
        }

        $this->studyPlanRepository->syncPlanSubjects(
            studyPlanId: $studyPlan->id,
            subjectIds: $subjectIds
        );

        $updateData['subjects_count'] = count($subjectIds);
    }

    public function getScheduleForUser(int $userId, int $days = 7): array
    {
        $timezone = config('app.timezone');

        $now = CarbonImmutable::now($timezone);

        $fromDate = $now->startOfDay();
        $toDate = $fromDate->addDays($days - 1)->endOfDay();

        $taskRemindersEnabled = $this->studyPlanRepository->getUserTaskReminderSetting($userId);

        if (! $taskRemindersEnabled) {
            return [
                'timezone' => $timezone,
                'task_reminders_enabled' => false,
                'should_cancel_existing_alarms' => true,
                'days' => $days,
                'generated_at' => $now->toDateTimeString(),
                'alarms' => [],
            ];
        }

        $occurrences = $this->studyPlanRepository->getDefaultPlanReminderOccurrences(
            userId: $userId,
            fromDate: $fromDate,
            toDate: $toDate,
        );

        $alarms = $occurrences
            ->map(function (object $occurrence) use ($now, $timezone) {
                $scheduledStartAt = CarbonImmutable::parse(
                    "{$occurrence->occurrence_date} {$occurrence->scheduled_start_time}",
                    $timezone
                );

                $scheduledEndAt = CarbonImmutable::parse(
                    "{$occurrence->occurrence_date} {$occurrence->scheduled_end_time}",
                    $timezone
                );

                $offsetMinutes = (int) $occurrence->reminder_offset_minutes;

                $alarmAt = $scheduledStartAt->subMinutes($offsetMinutes);

                return [
                    'alarm_key' => "study_task_alarm:{$occurrence->occurrence_id}",

                    'should_schedule_alarm' => $alarmAt->greaterThan($now),

                    'alarm_at' => $alarmAt->toDateTimeString(),

                    'reminder_offset_minutes' => $offsetMinutes,

                    'occurrence' => [
                        'id' => (int) $occurrence->occurrence_id,
                        'date' => (string) $occurrence->occurrence_date,
                        'scheduled_start_at' => $scheduledStartAt->toDateTimeString(),
                        'scheduled_end_at' => $scheduledEndAt->toDateTimeString(),
                    ],

                    'task' => [
                        'id' => (int) $occurrence->task_id,
                        'title' => $occurrence->task_title,
                        'description' => $occurrence->task_description,
                        'status' => $occurrence->task_status,
                    ],

                    'study_plan' => [
                        'id' => (int) $occurrence->study_plan_id,
                        'title' => $occurrence->study_plan_title,
                        'is_default' => (bool) $occurrence->is_default,
                    ],
                ];
            })
            ->filter(fn (array $alarm) => $alarm['should_schedule_alarm'] === true)
            ->values()
            ->all();

        return [
            'timezone' => $timezone,
            'task_reminders_enabled' => true,
            'should_cancel_existing_alarms' => false,
            'days' => $days,
            'generated_at' => $now->toDateTimeString(),
            'alarms' => $alarms,
        ];
    }
}
