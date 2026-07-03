<?php

namespace App\Services\Tests;

use App\DTOs\Notifications\NotificationPayload;
use App\Events\TestReviewStateChanged;
use App\Exceptions\Api\TestException;
use App\Helpers\BuildActor;
use App\Repositories\Tests\TestReviewRepository;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Support\Facades\DB;

class TestReviewService
{
    public function __construct(
        private readonly TestReviewRepository $repository,
        private readonly NotificationCenter $notificationCenter,
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

            $this->sendTestReviewNotification($eventPayload);
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

    ///////////////////////////////////////////////////////////////////////////

    public function storeFeedback(int $reviewId, int $userId, string $vote): void
    {
        DB::transaction(function () use ($reviewId, $userId, $vote) {

            $review = $this->repository->findReviewForFeedback($reviewId);

            if (! $review) {
                throw TestException::reviewNotAvailable();
            }

            if ((int) $review->reviewer_user_id === $userId) {
                throw TestException::cannotVoteOnOwnReview();
            }

            $currentFeedback = $this->repository->findUserFeedbackForUpdate(
                reviewId: $reviewId,
                userId: $userId
            );

            if ($currentFeedback) {
                if ($currentFeedback->vote === $vote) {
                    throw TestException::alreadyVoted();
                }

                $this->repository->updateUserFeedbackVote(
                    feedbackId: (int) $currentFeedback->id,
                    newVote: $vote
                );

                $this->repository->decrementHelpfulCounter(
                    reviewId: $reviewId,
                    vote: $currentFeedback->vote
                );

                $this->repository->incrementHelpfulCounter(
                    reviewId: $reviewId,
                    vote: $vote
                );

                return;
            }

            $created = $this->repository->createFeedbackIfMissing(
                reviewId: $reviewId,
                userId: $userId,
                vote: $vote
            );

            if (! $created) {
                throw TestException::alreadyVoted();
            }

            $this->repository->incrementHelpfulCounter(
                reviewId: $reviewId,
                vote: $vote
            );

        });
    }

    public function deleteFeedback(int $reviewId, int $userId): void
    {
        DB::transaction(function () use ($reviewId, $userId) {
            $review = $this->repository->findReviewForFeedback($reviewId);

            if (! $review) {
                throw TestException::reviewNotAvailable();
            }

            $feedback = $this->repository->findUserFeedback(
                reviewId: $reviewId,
                userId: $userId
            );

            if (! $feedback) {
                throw TestException::feedbackNotFound();
            }

            $deleted = $this->repository->deleteUserFeedback(
                reviewId: $reviewId,
                userId: $userId
            );

            if ($deleted) {
                $this->repository->decrementHelpfulCounter(
                    reviewId: $reviewId,
                    vote: $feedback->vote
                );
            }
        });
    }

    private function sendTestReviewNotification(array $eventPayload): void
    {

        $payload = NotificationPayload::make(
            title: 'عملية تعليق على اختبارك',
            body: 'قام المستخدم بتسجيل إعجابه بالاختبار الخاص بك',
            metadata: [
                'type' => 'test_Reviewed',
                'category' => 'social',

                'presentation' => [
                    'mode' => 'user',
                    'floor_color' => null,
                    'icon' => null,
                ],

                'actor' => BuildActor::buildUserActor((int) $eventPayload['actor_user_id']),

                'navigation' => [
                    'screen' => 'my_test_details',
                    'action' => 'open',
                ],

                'params' => [
                    'test_id' => (int) $eventPayload['test_id'],
                    'actor_user_id' => (int) $eventPayload['actor_user_id'],
                ],

            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $eventPayload['creator_user_id'],
            payload: $payload,
        );
    }
}
