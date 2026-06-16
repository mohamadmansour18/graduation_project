<?php

namespace App\Services\Admin;

use App\Enums\TestReviewStatus;
use App\Repositories\Admin\TestDashboardRepository;
use Carbon\CarbonImmutable;

class TestDashboardService
{
    public function __construct(
        private readonly TestDashboardRepository $repository
    ) {
    }

    public function getManagementBoard(?string $date = null): array
    {
        $selectedDate = $this->resolveSelectedDate($date);

        $statuses = $this->boardStatuses();

        $tests = $this->repository->getTestsWhoseCurrentStatusChangedBetween(
            startOfDay: $selectedDate->startOfDay(),
            endOfDay: $selectedDate->endOfDay(),
            statuses: array_values($statuses)
        );

        $testsGroupedByStatus = $tests->groupBy(function ($test) {
            return $this->statusValue($test->review_status);
        });

        $columns = [];

        foreach ($statuses as $columnKey => $status) {
            $columns[$columnKey] = $testsGroupedByStatus
                ->get($status->value, collect())
                ->values();
        }

        return [
            'selected_date' => $selectedDate->toDateString(),
            'columns' => $columns,
        ];
    }

    private function boardStatuses(): array
    {
        return [
            'new' => TestReviewStatus::New,
            'approved' => TestReviewStatus::Approved,
            'needs_revision' => TestReviewStatus::NeedsRevision,
            'under_review' => TestReviewStatus::UnderReview,
            'deleted' => TestReviewStatus::Deleted,
            'reported' => TestReviewStatus::Reported,
        ];
    }

    private function resolveSelectedDate(?string $date): CarbonImmutable
    {
        $timezone = config('app.timezone');

        if ($date) {
            return CarbonImmutable::createFromFormat('Y-m-d', $date, $timezone);
        }

        return now($timezone)->toImmutable();
    }

    private function statusValue(mixed $status): string
    {
        return $status instanceof TestReviewStatus
            ? $status->value
            : (string) $status;
    }
}
