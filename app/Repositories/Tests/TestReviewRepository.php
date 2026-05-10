<?php

namespace App\Repositories\Tests;

use App\Enums\PaymentStatus;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Models\Test;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TestReviewRepository
{
    public function findReviewableTestForUpdate(int $testId): Builder|Model|null
    {
        return Test::query()
            ->select([
                'id',
                'creator_user_id',
                'test_type',
                'review_status',
                'price',
                'reviews_count',
                'average_rating',
            ])
            ->where('id', $testId)
            ->where('test_type', TestType::Public->value)
            ->where('review_status', TestReviewStatus::Approved->value)
            ->lockForUpdate()
            ->first();
    }

    public function hasUserPurchasedTest(int $testId, int $userId): bool
    {
        return DB::table('test_purchases')
            ->where('test_id', $testId)
            ->where('buyer_user_id', $userId)
            ->where('payment_status', PaymentStatus::Paid->value)
            ->exists();
    }

    public function createReviewIfMissing(int $testId, int $userId, int $rating, string $reviewText , Carbon $createdAt): bool {

        $inserted = DB::table('test_reviews')->insertOrIgnore([
            'test_id' => $testId,
            'user_id' => $userId,
            'rating' => $rating,
            'review_text' => $reviewText,
            'helpful_yes_count' => 0,
            'helpful_no_count' => 0,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return $inserted === 1;
    }

    public function findUserReviewForUpdate(int $testId, int $userId): ?object
    {
        return DB::table('test_reviews')
            ->where('test_id', $testId)
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();
    }

    public function updateReview(int $reviewId, array $data): void
    {
        DB::table('test_reviews')
            ->where('id', $reviewId)
            ->update($data + [
                    'updated_at' => now(),
                ]);
    }

    public function deleteReview(int $reviewId): void
    {
        DB::table('test_reviews')
            ->where('id', $reviewId)
            ->delete();
    }

    public function deleteReviewFeedbacks(int $reviewId): void
    {
        DB::table('test_review_feedbacks')
            ->where('test_review_id', $reviewId)
            ->delete();
    }

    public function resetReviewHelpfulCounters(int $reviewId): void
    {
        DB::table('test_reviews')
            ->where('id', $reviewId)
            ->update([
                'helpful_yes_count' => 0,
                'helpful_no_count' => 0,
                'updated_at' => now(),
            ]);
    }

    public function incrementTestReviewsCountAndUpdateAverage(int $testId, int $oldReviewsCount, float $oldAverage, int $newRating): void
    {
        $newReviewsCount = $oldReviewsCount + 1;

        $newAverage = (($oldAverage * $oldReviewsCount) + $newRating) / $newReviewsCount;

        DB::table('test')
            ->where('id', $testId)
            ->update([
                'reviews_count' => $newReviewsCount,
                'average_rating' => round($newAverage, 2),
                'updated_at' => now(),
            ]);
    }

    public function decrementTestReviewsCountAndUpdateAverage(int $testId, int $oldReviewsCount, float $oldAverage, int $deletedRating): void
    {
        if ($oldReviewsCount <= 1) {
            DB::table('test')
                ->where('id', $testId)
                ->update([
                    'reviews_count' => 0,
                    'average_rating' => 0,
                    'updated_at' => now(),
                ]);

            return;
        }

        $newReviewsCount = $oldReviewsCount - 1;

        $newAverage = (($oldAverage * $oldReviewsCount) - $deletedRating) / $newReviewsCount;

        DB::table('test')
            ->where('id', $testId)
            ->update([
                'reviews_count' => $newReviewsCount,
                'average_rating' => round($newAverage, 2),
                'updated_at' => now(),
            ]);
    }

    public function updateTestAverageAfterRatingChange(int $testId, int $reviewsCount, float $oldAverage, int $oldRating, int $newRating): void
    {
        if ($reviewsCount <= 0) {
            return;
        }

        $newAverage = (($oldAverage * $reviewsCount) - $oldRating + $newRating) / $reviewsCount;

        DB::table('test')
            ->where('id', $testId)
            ->update([
                'average_rating' => round($newAverage, 2),
                'updated_at' => now(),
            ]);
    }

    public function refreshCreatorAverageTestRating(int $creatorUserId): void
    {
        $average = DB::table('test')
            ->where('creator_user_id', $creatorUserId)
            ->where('test_type', TestType::Public->value)
            ->where('review_status', TestReviewStatus::Approved->value)
            ->where('reviews_count', '>', 0)
            ->avg('average_rating');

        DB::table('user_profile_stats')
            ->where('user_id', $creatorUserId)
            ->update([
                'average_test_rating' => round((float) ($average ?? 0), 2),
                'updated_at' => now(),
            ]);
    }

}
