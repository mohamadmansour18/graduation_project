<?php

namespace App\Services\Tests;

use App\Events\TestReviewStateChanged;
use App\Exceptions\Api\TestException;
use App\Repositories\Tests\TestReviewRepository;
use Illuminate\Support\Facades\DB;

class TestReviewService
{
    public function __construct(
        private readonly TestReviewRepository $repository
    ) {}

    public function store(int $testId, int $userId, int $rating, string $reviewText): void
    {
        $eventPayload = null;

        DB::transaction(function () use ($testId, $userId, $rating, $reviewText, &$eventPayload) {
            $test = $this->repository->findReviewableTestForUpdate($testId);

            if (! $test) {
                throw TestException::testNotAvailableForReview();
            }

            $this->ensureUserCanReviewTest($test, $userId);

            $createdAt = now();

            $created = $this->repository->createReviewIfMissing(
                testId: $testId,
                userId: $userId,
                rating: $rating,
                reviewText: trim($reviewText),
                createdAt: $createdAt
            );

            if (! $created) {
                throw TestException::alreadyReviewed();
            }

            $this->repository->incrementTestReviewsCountAndUpdateAverage(
                testId: $testId,
                oldReviewsCount: (int) $test->reviews_count,
                oldAverage: (float) $test->average_rating,
                newRating: $rating
            );

            $this->repository->refreshCreatorAverageTestRating(
                creatorUserId: (int) $test->creator_user_id
            );

            $eventPayload = [
                'test_id' => $testId,
                'creator_user_id' => (int) $test->creator_user_id,
                'actor_user_id' => $userId,
                'delta' => 1,
                'effective_at' => now(),
            ];
        });

        if ($eventPayload !== null) {
            event(new TestReviewStateChanged(...$eventPayload));
        }
    }

    public function update(int $testId, int $userId, ?int $rating, ?string $reviewText): void
    {
        DB::transaction(function () use ($testId, $userId, $rating, $reviewText) {

            $test = $this->repository->findReviewableTestForUpdate($testId);

            if (! $test) {
                throw TestException::testNotAvailableForReview();
            }

            $review = $this->repository->findUserReviewForUpdate($testId, $userId);

            if (! $review) {
                throw TestException::reviewNotFound();
            }

            $updates = [];
            if ($rating !== null && $rating !== (int) $review->rating) {
                $updates['rating'] = $rating;

                $this->repository->updateTestAverageAfterRatingChange(
                    testId: $testId,
                    reviewsCount: (int) $test->reviews_count,
                    oldAverage: (float) $test->average_rating,
                    oldRating: (int) $review->rating,
                    newRating: $rating
                );

                $this->repository->refreshCreatorAverageTestRating(
                    creatorUserId: (int) $test->creator_user_id
                );
            }

            if ($reviewText !== null) {
                $newText = trim($reviewText);
                $oldText = trim((string) $review->review_text);

                if ($newText !== $oldText) {
                    $updates['review_text'] = $newText;

                    $this->repository->deleteReviewFeedbacks((int) $review->id);
                    $this->repository->resetReviewHelpfulCounters((int) $review->id);
                }
            }

            if ($updates === []) {
                throw TestException::nothingToUpdate();
            }

            $this->repository->updateReview((int) $review->id, $updates);
        });
    }

    public function delete(int $testId, int $userId): void
    {
        $eventPayload = null;

        DB::transaction(function () use ($testId, $userId, &$eventPayload) {
            $test = $this->repository->findReviewableTestForUpdate($testId);

            if (! $test) {
                throw TestException::testNotAvailableForReview();
            }

            $review = $this->repository->findUserReviewForUpdate($testId, $userId);

            if (! $review) {
                throw TestException::reviewNotFound();
            }

            $this->repository->deleteReview((int) $review->id);

            $this->repository->decrementTestReviewsCountAndUpdateAverage(
                testId: $testId,
                oldReviewsCount: (int) $test->reviews_count,
                oldAverage: (float) $test->average_rating,
                deletedRating: (int) $review->rating
            );

            $this->repository->refreshCreatorAverageTestRating(
                creatorUserId: (int) $test->creator_user_id
            );

            $eventPayload = [
                'test_id' => $testId,
                'creator_user_id' => (int) $test->creator_user_id,
                'actor_user_id' => $userId,
                'delta' => -1,
                'effective_at' => $review->created_at,
            ];
        });

        if ($eventPayload !== null) {
            event(new TestReviewStateChanged(...$eventPayload));
        }
    }

    private function ensureUserCanReviewTest(object $test, int $userId): void
    {
        if ((int) $test->creator_user_id === $userId) {
            throw TestException::cannotReviewOwnTest();
        }

        $isFree = is_null($test->price) || (float) $test->price <= 0;

        if ($isFree) {
            return;
        }

        $hasPurchased = $this->repository->hasUserPurchasedTest(
            testId: (int) $test->id,
            userId: $userId
        );

        if (! $hasPurchased) {
            throw TestException::purchaseRequiredForReview();
        }
    }
}
