<?php

namespace App\Repositories\Tests;

use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use Illuminate\Support\Facades\DB;

class TestReportReviewRepository
{
    public function lockReviewForReport(int $reviewId): ?object
    {
        return DB::table('test_reviews')
            ->join('test', 'test.id', '=', 'test_reviews.test_id')
            ->select([
                'test_reviews.id',
                'test_reviews.test_id',
                'test_reviews.user_id as reviewer_user_id',
                'test_reviews.rating',
                'test_reviews.helpful_yes_count',
                'test_reviews.helpful_no_count',
                'test_reviews.created_at',

                'test.reviews_count',
                'test.creator_user_id',
                'test.average_rating',
            ])
            ->where('test_reviews.id', $reviewId)
            ->where('test.test_type', TestType::Public->value)
            ->where('test.reviews_count' , '!=' , TestReviewStatus::Deleted->value)
            ->lockForUpdate()
            ->first();
    }

    public function createReportOrTouch(int $reviewId, int $userId, string $reason, ?string $description): bool
    {
        $now = now();

        $inserted = DB::table('test_review_reports')->insertOrIgnore([
            'test_review_id' => $reviewId,
            'user_id' => $userId,
            'reason' => $reason,
            'description' => $description,
            'reported_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($inserted === 1) {
            return true;
        }

        DB::table('test_review_reports')
            ->where('test_review_id', $reviewId)
            ->where('user_id', $userId)
            ->where('reason', $reason)
            ->update([
                'updated_at' => $now,
            ]);

        return false;
    }

    public function countReportsForReview(int $reviewId): int
    {
        return (int) DB::table('test_review_reports')
            ->where('test_review_id', $reviewId)
            ->count();
    }
    public function deleteReview(int $reviewId): void
    {
        DB::table('test_reviews')
            ->where('id', $reviewId)
            ->delete();
    }

    public function updateTestAfterReviewDeleted(int $testId, int $oldReviewsCount, float $oldAverage, int $deletedRating): void
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
