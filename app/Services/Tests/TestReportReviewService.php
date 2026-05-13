<?php

namespace App\Services\Tests;

use App\Events\TestReviewStateChanged;
use App\Exceptions\Api\TestException;
use App\Repositories\Tests\TestReportReviewRepository;
use App\Support\TestReviewReportThresholdPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestReportReviewService
{
    public function __construct(
        private readonly TestReportReviewRepository $repository,
        private readonly TestReviewReportThresholdPolicy $thresholdPolicy
    ) {}

    public function store(int $reviewId, int $reporterUserId, string $reason, ?string $description): void
    {
        $eventPayload = null;

        DB::transaction(function () use ($reviewId, $reporterUserId, $reason, $description , &$eventPayload) {
            $lockedReview = $this->repository->lockReviewForReport($reviewId);

            if (! $lockedReview) {
                throw TestException::reviewNotAvailable();
            }

            if ((int) $lockedReview->reviewer_user_id === $reporterUserId) {
                throw TestException::cannotReportOwnReview();
            }

            $isNewReport = $this->repository->createReportOrTouch(
                reviewId: $reviewId,
                userId: $reporterUserId,
                reason: $reason,
                description: $description ? trim($description) : null
            );

            if (! $isNewReport) {
                return;
            }

            $reportsCount = $this->repository->countReportsForReview($reviewId);

            $shouldDelete = $this->thresholdPolicy->shouldDeleteReview(
                reportsCount: $reportsCount,
                yesCount: (int) $lockedReview->helpful_yes_count,
                noCount: (int) $lockedReview->helpful_no_count
            );

            if (! $shouldDelete) {
                return;
            }

            $this->repository->deleteReview($reviewId);

            $this->repository->updateTestAfterReviewDeleted(
                testId: (int) $lockedReview->test_id,
                oldReviewsCount: (int) $lockedReview->reviews_count,
                oldAverage: (float) $lockedReview->average_rating,
                deletedRating: (int) $lockedReview->rating
            );

            $this->repository->refreshCreatorAverageTestRating(
                creatorUserId: (int) $lockedReview->creator_user_id
            );

            $eventPayload = [
                'test_id' => (int) $lockedReview->test_id,
                'creator_user_id' => (int) $lockedReview->creator_user_id,
                'actor_user_id' => $reporterUserId,
                'delta' => -1,
                'effective_at' => $lockedReview->created_at,
            ];

            Log::channel('audit')->info('Test review deleted automatically by report threshold.', [
                'test_review_id' => $reviewId,
                'test_id' => (int) $lockedReview->test_id,
                'reporter_user_id' => $reporterUserId,
                'reports_count' => $reportsCount,
                'helpful_yes_count' => (int) $lockedReview->helpful_yes_count,
                'helpful_no_count' => (int) $lockedReview->helpful_no_count,
            ]);
        });

        if ($eventPayload !== null) {
            event(new TestReviewStateChanged(...$eventPayload));
        }
    }

}
