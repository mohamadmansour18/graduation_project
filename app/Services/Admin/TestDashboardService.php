<?php

namespace App\Services\Admin;

use App\Enums\TestReviewStatus;
use App\Events\TestApproved;
use App\Events\TestManagementStatusChanged;
use App\Exceptions\Api\TestException;
use App\Models\Test;
use App\Models\User;
use App\Repositories\Admin\TestDashboardRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    /////////////////////////////////////////////////////////////

    public function getManagementTestDetails(int $testId): Test
    {
        $test = $this->repository->findManagementTestDetails($testId);

        if (! $test) {
            throw TestException::notFound();
        }

        return $test;
    }

    /////////////////////////////////////////////////////////////

    public function approveManagementTest(int $testId, User $reviewer): array
    {
        $result = DB::transaction(function () use ($testId, $reviewer) {
            $now = CarbonImmutable::now(config('app.timezone'));

            $test = $this->repository->findTestForApprovalWithLock($testId);

            if (! $test) {
                throw TestException::notFound();
            }

            $this->ensureTestCanBeApproved($test);

            $fromStatus = $this->normalizeStatus($test->review_status);
            $oldApprovalVersion = (int) ($test->current_approval_version ?? 0);

            $pendingRound = $this->repository->findPendingReviewRoundForTestWithLock($test->id);

            if (! $pendingRound) {
                throw TestException::pendingReviewRoundNotFound();
            }

            $isDirectApprovalAfterFalseReport = $fromStatus === TestReviewStatus::Reported;

            $isFirstPublicPublication = $oldApprovalVersion === 0;

            $newApprovalVersion = $isDirectApprovalAfterFalseReport
                ? $oldApprovalVersion
                : $oldApprovalVersion + 1;

            $publishedAt = $isFirstPublicPublication
                ? $now
                : $test->published_at;

            $shouldUpdatePublishCounters = $isFirstPublicPublication;

            $this->repository->approveReviewRound(
                round: $pendingRound,
                reviewerUserId: $reviewer->id,
                decidedAt: $now
            );

            $this->repository->markTestAsApproved(
                test: $test,
                newApprovalVersion: $newApprovalVersion,
                publishedAt: $publishedAt
            );

            $statusHistory = $this->repository->createStatusHistory(
                testId: $test->id,
                reviewRoundId: $pendingRound->id,
                fromStatus: $fromStatus,
                toStatus: TestReviewStatus::Approved,
                changedByUserId: $reviewer->id,
                note: 'تمت الموافقة على نشر الاختبار من لوحة التحكم'
            );

            return [
                'test_id' => $test->id,
                'creator_user_id' => $test->creator_user_id,
                'from_status' => $fromStatus->value,
                'to_status' => TestReviewStatus::Approved->value,
                'current_approval_version' => $newApprovalVersion,
                'old_approval_version' => $oldApprovalVersion,
                'published_at' => $publishedAt?->toDateTimeString(),
                'changed_at' => $statusHistory->created_at?->toDateTimeString() ?? $now->toDateTimeString(),
                'changed_date' => ($statusHistory->created_at ?? $now)->toDateString(),
                'should_update_publish_counters' => $shouldUpdatePublishCounters,
            ];
        });

        TestManagementStatusChanged::dispatch(
            $result['test_id'],
            $result['from_status'],
            $result['to_status'],
            $result['changed_date'],
            $result['changed_at'],
            $result['current_approval_version']
        );

        TestApproved::dispatch(
            $result['test_id'],
            $result['creator_user_id'],
            CarbonImmutable::parse($result['changed_at'], config('app.timezone')),
            $result['current_approval_version'],
            $result['should_update_publish_counters']
        );

        Log::channel('audit')->info('test_approved_from_dashboard', [
            'test_id' => $result['test_id'],
            'from_status' => $result['from_status'],
            'to_status' => $result['to_status'],
            'current_approval_version' => $result['current_approval_version'],
        ]);

        return [
            'id' => $result['test_id'],
            'review_status' => $result['to_status'],
            'current_approval_version' => $result['current_approval_version'],
            'published_at' => $result['published_at'],
        ];
    }

    private function ensureTestCanBeApproved(Test $test): void
    {
        if ($this->isPrivateTest($test)) {
            throw TestException::privateTestDoesNotNeedReview();
        }

        if ($test->trashed()) {
            throw TestException::deletedTestCannotBeApproved();
        }

        $status = $this->normalizeStatus($test->review_status);

        match ($status) {
            TestReviewStatus::Approved => throw TestException::testAlreadyApproved(),
            TestReviewStatus::Deleted => throw TestException::deletedTestCannotBeApproved(),
            TestReviewStatus::NeedsRevision => throw TestException::needsRevisionTestCannotBeApproved(),

            TestReviewStatus::New,
            TestReviewStatus::UnderReview,
            TestReviewStatus::Reported => null,

            default => throw TestException::testCannotBeApprovedFromCurrentStatus($status->value),
        };
    }

    private function normalizeStatus(mixed $status): TestReviewStatus
    {
        return $status instanceof TestReviewStatus
            ? $status
            : TestReviewStatus::from($status);
    }

    private function isPrivateTest(Test $test): bool
    {
        $value = $test->test_type instanceof \BackedEnum
            ? $test->test_type->value
            : $test->test_type;

        return $value === 'خاص';
    }
}
