<?php

namespace App\Services\StudyPlans;

use App\Http\Resources\DailyTaskResource;
use App\Http\Resources\StudyPlanDaySummaryResource;
use App\Http\Resources\StudyPlanOverviewResource;
use App\Repositories\StudyPlans\DailyTaskRepository;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DailyTaskService
{
    public function __construct(
        private readonly DailyTaskRepository $studyPlanRepository,
    ) {}

    public function getOverview(int $userId, string $selectedDate, string $rangeStart, string $rangeEnd,): array
    {
        $today = now()->toDateString();

        $defaultPlan = $this->studyPlanRepository->findDefaultForUser($userId);

        $userSettings = $this->studyPlanRepository->getUserSettings($userId);

        if (! $defaultPlan) {
            return [
                'userSettings' => $userSettings ?? [],
                'server_today' => $today,
                'selected_date' => $selectedDate,
                'range' => [
                    'start' => $rangeStart,
                    'end' => $rangeEnd,
                ],
                'has_default_plan' => false,
                'plan' => null,
                'days' => [],
                'tasks' => [],
            ];
        }

        $dailySummaries = $this->studyPlanRepository->getRangeDailySummaries(
            studyPlanId: $defaultPlan->id,
            rangeStart: $rangeStart,
            rangeEnd: $rangeEnd,
        );

        $days = $this->buildDays(
            rangeStart: $rangeStart,
            rangeEnd: $rangeEnd,
            today: $today,
            dailySummaries: $dailySummaries,
        );

        $tasks = $this->studyPlanRepository->getTasksForDate(
            studyPlanId: $defaultPlan->id,
            selectedDate: $selectedDate,
        )->values()->map(function ($task, int $index) {
            $task->task_number = $index + 1;

            return $task;
        });

        return [
            'userSettings' => $userSettings,
            'server_today' => $today,
            'selected_date' => $selectedDate,
            'range' => [
                'start' => $rangeStart,
                'end' => $rangeEnd,
            ],
            'has_default_plan' => true,
            'is_selected_date_inside_plan' => $this->isDateInsidePlan($selectedDate, $defaultPlan->start_date, $defaultPlan->end_date),
            'plan' => new StudyPlanOverviewResource($defaultPlan),
            'days' => StudyPlanDaySummaryResource::collection($days),
            'tasks' => DailyTaskResource::collection($tasks->values()),
        ];
    }

    private function buildDays(string $rangeStart, string $rangeEnd, string $today, $dailySummaries): array
    {
        $period = CarbonPeriod::create($rangeStart, $rangeEnd);
        $days = [];

        foreach ($period as $date) {
            $dateString = $date->toDateString();
            $summary = $dailySummaries->get($dateString);

            $totalTasks = (int) ($summary?->total_tasks ?? 0);
            $completedTasks = (int) ($summary?->completed_tasks ?? 0);

            $completionState = $this->resolveCompletionState($totalTasks, $completedTasks);
            $displayState = $this->resolveDisplayState(
                date: $dateString,
                today: $today,
                totalTasks: $totalTasks,
                completedTasks: $completedTasks,
            );

            $days[] = [
                'date' => $dateString,
                'day_number' => $date->day,
                'day_name' => $this->arabicDayName($date),
                'is_today' => $dateString === $today,
                'has_tasks' => $totalTasks > 0,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'completion_state' => $completionState,
                'display_state' => $displayState,
            ];
        }

        return $days;
    }

    private function resolveCompletionState(int $totalTasks, int $completedTasks): string
    {
        if ($totalTasks === 0) {
            return 'empty';
        }

        if ($completedTasks === $totalTasks) {
            return 'completed';
        }

        return 'incomplete';
    }

    private function resolveDisplayState(string $date, string $today, int $totalTasks, int $completedTasks): string
    {
        if ($date === $today) {
            return 'today';
        }

        if ($totalTasks === 0) {
            return 'empty';
        }

        if ($completedTasks === $totalTasks) {
            return 'completed';
        }

        if ($date > $today) {
            return 'scheduled';
        }

        return 'incomplete';
    }

    private function isDateInsidePlan(string $selectedDate, mixed $startDate, mixed $endDate): bool
    {
        $selectedDate = Carbon::parse($selectedDate)->toDateString();

        return $selectedDate >= Carbon::parse($startDate)->toDateString()
            && $selectedDate <= Carbon::parse($endDate)->toDateString();
    }

    private function arabicDayName(Carbon $date): string
    {
        return match ($date->dayOfWeek) {
            Carbon::SATURDAY => 'السبت',
            Carbon::SUNDAY => 'الأحد',
            Carbon::MONDAY => 'الإثنين',
            Carbon::TUESDAY => 'الثلاثاء',
            Carbon::WEDNESDAY => 'الأربعاء',
            Carbon::THURSDAY => 'الخميس',
            Carbon::FRIDAY => 'الجمعة',
        };
    }
}
