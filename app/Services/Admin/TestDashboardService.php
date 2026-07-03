<?php

namespace App\Services\Admin;

use App\DTOs\Notifications\NotificationPayload;
use App\Enums\TestReviewStatus;
use App\Events\TestApproved;
use App\Events\TestDashboardDeleted;
use App\Events\TestManagementRevisionRequested;
use App\Events\TestManagementStatusChanged;
use App\Events\TestReviewStateChanged;
use App\Exceptions\Api\TestException;
use App\Helpers\ImageProcessor;
use App\Models\Test;
use App\Models\User;
use App\Repositories\Admin\TestDashboardRepository;
use App\Services\Notifications\NotificationCenter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestDashboardService
{
    public function __construct(
        private readonly TestDashboardRepository $repository,
        private readonly NotificationCenter $notificationCenter,
    )
    {}

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
            : (string)$status;
    }

    /////////////////////////////////////////////////////////////

    public function getManagementTestDetails(int $testId): Test
    {
        $test = $this->repository->findManagementTestDetails($testId);

        if (!$test) {
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

            if (!$test) {
                throw TestException::notFound();
            }

            $this->ensureTestCanBeApproved($test);

            $fromStatus = $this->normalizeStatus($test->review_status);
            $oldApprovalVersion = (int)($test->current_approval_version ?? 0);

            $pendingRound = $this->repository->findPendingReviewRoundForTestWithLock($test->id);

            if (!$pendingRound) {
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
                'test_title' => $test->title,
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

        $this->sendTestApprovedNotification($result);

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

    /////////////////////////////////////////////////////////////

    public function deleteManagementTest(int $testId, User $reviewer, string $deletionReason): array
    {
        $result = DB::transaction(function () use ($testId, $reviewer, $deletionReason) {
            $now = CarbonImmutable::now(config('app.timezone'));

            $test = $this->repository->findTestForDeletionWithLock($testId);

            if (!$test) {
                throw TestException::NotFound();
            }

            $this->ensureTestCanBeDeleted($test);

            $fromStatus = $this->normalizeStatus($test->review_status);

            $hasSuccessfulPurchases = $this->repository->hasSuccessfulPurchases($test->id);

            $isPaid = !is_null($test->price) && (float)$test->price > 0;

            $shouldSoftDelete = $isPaid && $hasSuccessfulPurchases;

            $oldApprovalVersion = (int)($test->current_approval_version ?? 0);

            $wasPublishedBefore = $oldApprovalVersion > 0;

            $publishedAt = $test->published_at
                ? CarbonImmutable::parse($test->published_at, config('app.timezone'))
                : null;

            $pendingRound = $this->repository->findPendingReviewRoundForTestWithLock($test->id);

            if ($pendingRound) {
                $this->repository->markReviewRoundAsDeleted(
                    round: $pendingRound,
                    reviewerUserId: $reviewer->id,
                    decidedAt: $now
                );
            }

            if ($shouldSoftDelete) {
                $this->repository->markTestAsDeleted($test);

                $this->repository->createStatusHistory(
                    testId: $test->id,
                    reviewRoundId: $pendingRound?->id,
                    fromStatus: $fromStatus,
                    toStatus: TestReviewStatus::Deleted,
                    changedByUserId: $reviewer->id,
                    note: $deletionReason
                );

                $this->repository->softDeleteTest($test);
            } else {
                $this->repository->forceDeleteTest($test);
            }

            return [
                'test_id' => $testId,
                'test_title' => $test->title,
                'deletion_reason' => $deletionReason,
                'creator_user_id' => $test->creator_user_id,
                'from_status' => $fromStatus->value,
                'to_status' => TestReviewStatus::Deleted->value,
                'deleted_at' => $now->toDateTimeString(),
                'deleted_date' => $now->toDateString(),
                'deletion_type' => $shouldSoftDelete ? 'soft_delete' : 'force_delete',
                'should_appear_in_deleted_column' => $shouldSoftDelete,
                'should_decrease_publish_counters' => $wasPublishedBefore,
                'published_at' => $publishedAt?->toDateTimeString(),
                'published_year' => $publishedAt?->year,
                'published_month' => $publishedAt?->month,
                'reviewer_user_id' => $reviewer->id,
            ];
        });

        TestManagementStatusChanged::dispatch(
            $result['test_id'],
            $result['from_status'],
            $result['to_status'],
            $result['deleted_date'],
            $result['deleted_at'],
            0,
            $result['deletion_type'],
            $result['should_appear_in_deleted_column']
        );

        TestDashboardDeleted::dispatch(
            $result['test_id'],
            $result['creator_user_id'],
            CarbonImmutable::parse($result['deleted_at'], config('app.timezone')),
            $result['published_year'],
            $result['published_month'],
            $result['should_decrease_publish_counters']
        );

        Log::channel('audit')->info('test_deleted_from_dashboard', [
            'test_id' => $result['test_id'],
            'reviewer_user_id' => $result['reviewer_user_id'],
            'from_status' => $result['from_status'],
            'to_status' => $result['to_status'],
            'deletion_type' => $result['deletion_type'],
            'should_decrease_publish_counters' => $result['should_decrease_publish_counters'],
        ]);

        $this->sendTestDeletedNotification($result);

        return [
            'id' => $result['test_id'],
            'review_status' => $result['to_status'],
            'deletion_type' => $result['deletion_type'],
        ];
    }

    private function ensureTestCanBeDeleted(Test $test): void
    {
        if ($this->isPrivateTest($test)) {
            throw TestException::privateTestCannotBeDeletedFromDashboard();
        }

        if ($test->trashed()) {
            throw TestException::deletedTestCannotBeDeletedAgain();
        }

        $status = $this->normalizeStatus($test->review_status);

        if ($status === TestReviewStatus::Deleted) {
            throw TestException::deletedTestCannotBeDeletedAgain();
        }
    }

    /////////////////////////////////////////////////////////////

    public function requestManagementTestRevisions(int $testId, User $reviewer, array $revisions): array
    {
        $result = DB::transaction(function () use ($testId, $reviewer, $revisions) {
            $now = CarbonImmutable::now(config('app.timezone'));

            $test = $this->repository->findTestForRevisionRequestWithLock($testId);

            if (!$test) {
                throw TestException::NotFound();
            }

            $this->ensureTestCanReceiveRevisionRequests($test);

            $fromStatus = $this->normalizeStatus($test->review_status);

            [$round, $statusChanged] = $this->resolveReviewRoundForRevisionRequest(
                test: $test,
                fromStatus: $fromStatus,
                reviewer: $reviewer,
                decidedAt: $now
            );

            $existingRequestsCount = $this->repository->countRevisionRequestsForRound($round->id);
            $newRequestsCount = count($revisions);
            $totalRequestsCount = $existingRequestsCount + $newRequestsCount;

            if ($totalRequestsCount > 8) {
                throw TestException::revisionRequestsLimitExceeded(
                    remaining: max(8 - $existingRequestsCount, 0)
                );
            }

            $normalizedRequests = $this->normalizeRevisionRequests(
                testId: $test->id,
                revisions: $revisions
            );

            $createdRequests = $this->repository->createRevisionRequests(
                testId: $test->id,
                roundId: $round->id,
                createdByUserId: $reviewer->id,
                requests: $normalizedRequests
            );

            if ($statusChanged) {
                $this->repository->markTestAsNeedsRevision($test);

                $this->repository->createStatusHistory(
                    testId: $test->id,
                    reviewRoundId: $round->id,
                    fromStatus: $fromStatus,
                    toStatus: TestReviewStatus::NeedsRevision,
                    changedByUserId: $reviewer->id,
                    note: 'طلب المشرف تعديلات على الاختبار'
                );
            }

            return [
                'test_id' => $test->id,
                'creator_user_id' => (int) $test->creator_user_id,
                'test_title' => $test->title,
                'review_round_id' => $round->id,
                'from_status' => $fromStatus->value,
                'to_status' => TestReviewStatus::NeedsRevision->value,
                'status_changed' => $statusChanged,
                'created_revision_requests_count' => $createdRequests->count(),
                'total_revision_requests_count' => $totalRequestsCount,
                'changed_at' => $now->toDateTimeString(),
                'changed_date' => $now->toDateString(),
                'reviewer_user_id' => $reviewer->id,
            ];
        });

        TestManagementRevisionRequested::dispatch(
            $result['test_id'],
            $result['review_round_id'],
            $result['from_status'],
            $result['to_status'],
            $result['changed_date'],
            $result['changed_at'],
            $result['status_changed'],
            $result['created_revision_requests_count'],
            $result['total_revision_requests_count']
        );

        Log::channel('audit')->info('test_revision_requested_from_dashboard', [
            'test_id' => $result['test_id'],
            'review_round_id' => $result['review_round_id'],
            'reviewer_user_id' => $result['reviewer_user_id'],
            'from_status' => $result['from_status'],
            'to_status' => $result['to_status'],
            'status_changed' => $result['status_changed'],
            'created_revision_requests_count' => $result['created_revision_requests_count'],
            'total_revision_requests_count' => $result['total_revision_requests_count'],
        ]);

        $this->sendTestRevisionRequestedNotification($result);

        return [
            'id' => $result['test_id'],
            'review_status' => $result['to_status'],
            'status_changed' => $result['status_changed'],
            'created_revision_requests_count' => $result['created_revision_requests_count'],
            'total_revision_requests_count' => $result['total_revision_requests_count'],
        ];
    }

    private function ensureTestCanReceiveRevisionRequests(Test $test): void
    {
        if ($this->isPrivateTest($test)) {
            throw TestException::privateTestDoesNotNeedReview();
        }

        if ($test->trashed()) {
            throw TestException::deletedTestCannotRequestRevisions();
        }

        $status = $this->normalizeStatus($test->review_status);

        match ($status) {
            TestReviewStatus::Deleted => throw TestException::deletedTestCannotRequestRevisions(),
            TestReviewStatus::Approved => throw TestException::approvedTestCannotRequestRevisions(),
            TestReviewStatus::UnderReview => throw TestException::underReviewTestCannotRequestRevisions(),

            TestReviewStatus::New,
            TestReviewStatus::Reported,
            TestReviewStatus::NeedsRevision => null,
        };
    }

    private function resolveReviewRoundForRevisionRequest(Test $test, TestReviewStatus $fromStatus, User $reviewer, CarbonImmutable $decidedAt): array
    {
        if ($fromStatus === TestReviewStatus::NeedsRevision) {
            $round = $this->repository->findLatestNeedsRevisionRoundForTestWithLock($test->id);

            if (!$round) {
                throw TestException::revisionRoundNotFound();
            }

            if ((int)$round->reviewer_user_id !== (int)$reviewer->id) {
                throw TestException::onlyOriginalReviewerCanAddRevisions();
            }

            return [$round, false];
        }

        $round = $this->repository->findPendingReviewRoundForTestWithLock($test->id);

        if (!$round) {
            throw TestException::pendingReviewRoundNotFound();
        }

        $this->repository->markReviewRoundAsNeedsRevision(
            round: $round,
            reviewerUserId: $reviewer->id,
            decidedAt: $decidedAt
        );

        return [$round, true];
    }


    private function normalizeRevisionRequests(int $testId, array $revisions): array
    {
        $normalized = [];

        foreach ($revisions as $revision) {
            $type = $revision['revision_type'];
            $questionPosition = $revision['question_position'] ?? null;
            $optionPosition = $revision['option_position'] ?? null;

            $targetQuestionId = null;
            $targetOptionId = null;

            if (in_array($type, ['نص السؤال', 'التلميح', 'إجابة السؤال', 'نص الاجابة'], true)) {

                $targetQuestionId = $this->repository->findQuestionIdByPosition(
                    testId: $testId,
                    position: (int)$questionPosition
                );

                if (!$targetQuestionId) {
                    throw TestException::questionPositionNotFound((int)$questionPosition);
                }
            }

            if ($type === 'نص الاجابة') {
                $targetOptionId = $this->repository->findOptionIdByQuestionIdAndPosition(
                    questionId: $targetQuestionId,
                    position: (int)$optionPosition
                );

                if (!$targetOptionId) {
                    throw TestException::optionPositionNotFound(
                        questionPosition: (int)$questionPosition,
                        optionPosition: (int)$optionPosition
                    );
                }
            }

            $normalized[] = [
                'revision_type' => $type,
                'target_question_id' => $targetQuestionId,
                'target_option_id' => $targetOptionId,
                'problem_note' => $revision['problem_note'],
            ];
        }

        return $normalized;
    }

    /////////////////////////////////////////////////////////////
    public function getQuestions(int $testId): array
    {
        $test = $this->repository->findTestWithContent($testId);

        if (!$test) {
            throw TestException::notFound();
        }

        return [
            'test' => $test,
        ];
    }

    /////////////////////////////////////////////////////////////
    public function getQuestionsSamples(int $testId): array
    {
        $test = $this->repository->findTestWithContent($testId, true);

        if (!$test) {
            throw TestException::notFound();
        }

        return [
            'test' => $test,
        ];
    }

    /////////////////////////////////////////////////////////////

    public function getManagementTestReviews(int $testId, ?int $rating = null, int $perPage = 20): array
    {
        $test = $this->repository->findTestForReviewsDashboard($testId);

        if (!$test) {
            throw TestException::NotFound();
        }

        if ($this->isPrivateTest($test)) {
            throw TestException::privateTestDoesNotNeedReview();
        }

        $ratingCounts = $this->repository->getRatingDistribution($test->id);

        $statistics = $this->repository->getReviewStatistics($test->id);

        $commentsPaginator = $this->repository->cursorPaginateTestComments(
            testId: $test->id,
            rating: $rating,
            perPage: $perPage
        );

        return [
            'test' => $test,
            'rating_information' => [
                'average_rating' => round((float)($test->average_rating ?? 0), 1),
                'ratings_count' => (int)($statistics->ratings_count ?? $test->reviews_count ?? 0),
                'comments_count' => (int)($statistics->comments_count ?? 0),
                'rating_distribution' => (object)$this->buildRatingDistribution($ratingCounts),
            ],
            'statistics' => [
                'comments_count' => (int)($statistics->comments_count ?? 0),
                'helpful_yes_count' => (int)($statistics->helpful_yes_count ?? 0),
                'helpful_no_count' => (int)($statistics->helpful_no_count ?? 0),
            ],
            'comments' => $commentsPaginator,
        ];
    }

    private function buildRatingDistribution(Collection $ratingCounts): array
    {
        $counts = [];

        for ($star = 1; $star <= 5; $star++) {
            $counts[$star] = (int)($ratingCounts->get($star, 0));
        }

        $total = array_sum($counts);

        if ($total === 0) {
            return [
                '1' => ['count' => 0, 'percentage' => 0],
                '2' => ['count' => 0, 'percentage' => 0],
                '3' => ['count' => 0, 'percentage' => 0],
                '4' => ['count' => 0, 'percentage' => 0],
                '5' => ['count' => 0, 'percentage' => 0],
            ];
        }

        $distribution = [];
        $percentageSum = 0.0;

        for ($star = 1; $star <= 5; $star++) {
            $percentage = round(($counts[$star] / $total) * 100, 1);

            $distribution[(string)$star] = [
                'count' => $counts[$star],
                'percentage' => $percentage,
            ];

            $percentageSum += $percentage;
        }

        $difference = round(100 - $percentageSum, 1);

        if ($difference !== 0.0) {
            $starToAdjust = array_keys($counts, max($counts), true)[0];

            $distribution[(string)$starToAdjust]['percentage'] = round(
                $distribution[(string)$starToAdjust]['percentage'] + $difference,
                1
            );
        }

        return $distribution;
    }

    /////////////////////////////////////////////////////////////

    public function deleteManagementTestReview(int $reviewId, User $actor): void
    {
        $result = DB::transaction(function () use ($reviewId, $actor) {

            $reviewSnapshot = $this->repository->findReviewForDashboardDeletion($reviewId);

            if (!$reviewSnapshot) {
                throw TestException::testReviewNotFound();
            }

            $test = $this->repository->findTestForReviewDeletionWithLock($reviewSnapshot->test_id);

            if (!$test) {
                throw TestException::NotFound();
            }

            if ($this->isPrivateTest($test)) {
                throw TestException::privateTestReviewCannotBeManagedFromDashboard();
            }

            $review = $this->repository->findReviewByIdWithLock($reviewId);

            if (!$review || (int)$review->test_id !== (int)$test->id) {
                throw TestException::testReviewNotFound();
            }

            $effectiveAt = $review->created_at;

            $this->repository->deleteReview($review);

            $reviewCounters = $this->repository->recalculateTestReviewCounters($test->id);

            $this->repository->updateTestReviewCounters(
                test: $test,
                reviewsCount: (int)$reviewCounters->reviews_count,
                averageRating: (float)$reviewCounters->average_rating
            );

            return [
                'test_id' => (int) $test->id,
                'test_title' => $test->title,
                'creator_user_id' => (int) $test->creator_user_id,

                'review_id' => (int) $reviewId,
                'review_owner_user_id' => (int) $review->user_id,
                'review_rating' => (int) $review->rating,

                'actor_user_id' => (int) $actor->id,
                'effective_at' => $effectiveAt,
            ];
        });

        TestReviewStateChanged::dispatch(
            $result['test_id'],
            $result['creator_user_id'],
            $result['actor_user_id'],
            -1,
            $result['effective_at'],
        );

        Log::channel('audit')->info('test_review_deleted_from_dashboard', [
            'test_id' => $result['test_id'],
            'review_id' => $result['review_id'],
            'creator_user_id' => $result['creator_user_id'],
            'actor_user_id' => $result['actor_user_id'],
        ]);

        $this->sendTestReviewDeletedFromDashboardNotification($result);
    }

    /////////////////////////////////////////////////////////////

    public function getManagementTestStatusHistory(int $testId): Test
    {
        $test = $this->repository->findTestStatusHistoryForDashboard($testId);

        if (!$test) {
            throw TestException::NotFound();
        }

        if ($this->isPrivateTest($test)) {
            throw TestException::privateTestDoesNotNeedReview();
        }

        return $test;
    }

    /////////////////////////////////////////////////////////////

    public function getManagementTestReports(int $testId, int $perPage = 20): array
    {
        $test = $this->repository->findTestForReportsDashboard($testId);

        if (!$test) {
            throw TestException::NotFound();
        }

        if ($this->isPrivateTest($test)) {
            throw TestException::privateTestDoesNotNeedReview();
        }

        $approvalVersion = (int)($test->current_approval_version ?? 0);

        $reasonCounters = $this->repository->getReportReasonCounters(
            testId: $test->id,
            approvalVersion: $approvalVersion
        );

        $totalReportsCount = $this->repository->getTotalReportsCount(
            testId: $test->id,
            approvalVersion: $approvalVersion
        );

        $reportsPaginator = $this->repository->cursorPaginateTestReports(
            testId: $test->id,
            approvalVersion: $approvalVersion,
            perPage: $perPage
        );

        return [
            'approval_version' => $approvalVersion,
            'statistics' => [
                'total_reports_count' => $totalReportsCount,
                'reasons' => $reasonCounters,
            ],
            'reports' => $reportsPaginator,
        ];
    }

    /////////////////////////////////////////////////////////////

    public function updateManagementTestRevisionRequests(int $testId, User $reviewer, array $revisions): void
    {
        $notificationPayload = null;

        DB::transaction(function () use ($testId, $reviewer, $revisions, &$notificationPayload) {
            $test = $this->repository->findTestForRevisionRequestsUpdateWithLock($testId);

            if (! $test) {
                throw TestException::NotFound();
            }

            $this->ensureCanUpdateRevisionRequests($test);

            $round = $this->repository->findLatestNeedsRevisionRoundForTestWithLock($test->id);

            if (! $round) {
                throw TestException::revisionRoundNotFound();
            }

            if ((int) $round->reviewer_user_id !== (int) $reviewer->id) {
                throw TestException::onlyOriginalReviewerCanUpdateRevisions();
            }

            $normalizedRequests = $this->normalizeRevisionRequests(
                testId: $test->id,
                revisions: $revisions
            );

            $this->repository->deleteRevisionRequestsForRound($round->id);

            $this->repository->createRevisionRequests2(
                testId: $test->id,
                roundId: $round->id,
                createdByUserId: $reviewer->id,
                requests: $normalizedRequests
            );

            $notificationPayload = [
                'test_id' => (int) $test->id,
                'test_title' => $test->title,
                'creator_user_id' => (int) $test->creator_user_id,
                'review_round_id' => (int) $round->id,
                'reviewer_user_id' => (int) $reviewer->id,
                'revision_requests_count' => count($normalizedRequests),
                'review_status' => TestReviewStatus::NeedsRevision->value,
                'changed_at' => now()->toDateTimeString(),
            ];

            Log::channel('audit')->info('test_revision_requests_updated_from_dashboard', [
                'test_id' => $test->id,
                'review_round_id' => $round->id,
                'reviewer_user_id' => $reviewer->id,
                'revision_requests_count' => count($normalizedRequests),
            ]);
        });

        if ($notificationPayload !== null) {
            $this->sendTestRevisionRequestsUpdatedNotification($notificationPayload);
        }
    }

    private function ensureCanUpdateRevisionRequests(Test $test): void
    {
        if ($this->isPrivateTest($test)) {
            throw TestException::privateTestDoesNotNeedReview();
        }

        if ($test->trashed()) {
            throw TestException::deletedTestCannotRequestRevisions();
        }

        $status = $this->normalizeStatus($test->review_status);

        if ($status !== TestReviewStatus::NeedsRevision) {
            throw TestException::testMustBeNeedsRevisionToUpdateRevisionRequests();
        }
    }

    private function sendTestApprovedNotification(array $data): void
    {
        $testTitle = $data['test_title'] ?? 'اختبارك';

        $payload = NotificationPayload::make(
            title: 'تمت الموافقة على نشر اختبارك',
            body: "تمت الموافقة على نشر اختبارك: {$testTitle}",
            metadata: [
                'type' => 'test_approved',
                'category' => 'test_review',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#E4FFE5',
                    'icon' => ImageProcessor::urlOrDefault('system-notification/true.svg' , 'defaults/notification.svg' , 'public'),
                ],

                'actor' => null,

                'navigation' => [
                    'screen' => 'my_test_details',
                    'action' => 'open',
                ],

                'params' => [
                    'test_id' => (int) $data['test_id'],
                ],

            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $data['creator_user_id'],
            payload: $payload,
        );
    }

    private function sendTestDeletedNotification(array $data): void
    {
        $testTitle = $data['test_title'] ?? 'اختبارك';

        $payload = NotificationPayload::make(
            title: 'تم حذف اختبارك',
            body: "تم حذف اختبارك: {$testTitle}. السبب: {$data['deletion_reason']}",
            metadata: [
                'type' => 'test_deleted_by_dashboard',
                'category' => 'test_review',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#FFE7E7',
                    'icon' => ImageProcessor::urlOrDefault('system-notification/trash.svg' , 'defaults/notification.svg' , 'public'),
                ],

                'actor' => null,

                'navigation' => [
                    'screen' => 'my_tests_details',
                    'action' => 'open',
                ],

                'params' => [
                    'test_id' => (int) $data['test_id'],
                    'deletion_type' => $data['deletion_type'],
                ],

            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $data['creator_user_id'],
            payload: $payload,
        );
    }

    private function sendTestRevisionRequestedNotification(array $data): void
    {
        $testTitle = $data['test_title'] ?? 'اختبارك';

        $payload = NotificationPayload::make(
            title: 'مطلوب تعديل اختبارك',
            body: "طلبت لوحة التحكم تعديلات على اختبارك: {$testTitle}",
            metadata: [
                'type' => 'test_revision_requested',
                'category' => 'test_review',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#E7F9FF',
                    'icon' => ImageProcessor::urlOrDefault('system-notification/feather.svg' , 'defaults/notification.svg' , 'public'),
                ],

                'actor' => null,

                'navigation' => [
                    'screen' => 'my_profile_details',
                    'action' => 'open',
                ],

                'params' => [
                    'test_id' => (int) $data['test_id'],
                ],

            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $data['creator_user_id'],
            payload: $payload,
        );
    }

    private function sendTestRevisionRequestsUpdatedNotification(array $data): void
    {
        $testTitle = $data['test_title'] ?? 'اختبارك';

        $payload = NotificationPayload::make(
            title: 'تم تعديل قائمة التعديلات المطلوبة',
            body: "تم تعديل قائمة التعديلات المطلوبة على اختبارك: {$testTitle}",
            metadata: [
                'type' => 'test_revision_requests_updated',
                'category' => 'test_review',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#E7F9FF',
                    'icon' => ImageProcessor::urlOrDefault('system-notification/feather.svg' , 'defaults/notification.svg' , 'public'),
                ],

                'actor' => null,

                'navigation' => [
                    'screen' => 'my_test_details',
                    'action' => 'open',
                ],

                'params' => [
                    'test_id' => (int) $data['test_id'],
                ],

            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $data['creator_user_id'],
            payload: $payload,
        );
    }

    private function sendTestReviewDeletedFromDashboardNotification(array $data): void
    {
        $testTitle = $data['test_title'] ?? 'أحد الاختبارات';

        $payload = NotificationPayload::make(
            title: 'تم حذف تعليقك',
            body: "تم حذف تعليقك على اختبار: {$testTitle} من قبل إدارة النظام.",
            metadata: [
                'type' => 'test_review_deleted_by_dashboard',
                'category' => 'moderation',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#FFE7E7',
                    'icon' => ImageProcessor::urlOrDefault('system-notification/trash.svg' , 'defaults/notification.svg' , 'public'),
                ],

                'actor' => null,

                'navigation' => [
                    'screen' => 'public_test_details',
                    'action' => 'open',

                ],

                'params' => [
                    'test_id' => (int) $data['test_id'],
                    'review_id' => (int) $data['review_id'],
                ],

            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $data['review_owner_user_id'],
            payload: $payload,
        );
    }
}
