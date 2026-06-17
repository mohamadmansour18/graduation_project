<?php

namespace App\Listeners;

use App\Events\TestApproved;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdatePublishedTestSummaryStats implements ShouldQueue
{
    use InteractsWithQueue;

    public bool $afterCommit = true;
    public int $tries = 2;
    public function handle(TestApproved $event): void
    {
        if (! $event->shouldUpdatePublishCounters) {
            return;
        }

        $approvedAt = $event->approvedAt;
        $year = (int) $approvedAt->format('Y');
        $month = (int) $approvedAt->format('n');
        $now = now();

        DB::transaction(function () use ($event, $year, $month, $approvedAt, $now) {

            $this->incrementUserYearlyTestStats(
                userId: $event->creatorUserId,
                year: $year,
                publishedAt: $approvedAt,
                now: $now
            );

            $this->incrementUserProfileStats(
                userId: $event->creatorUserId,
                now: $now
            );

            $this->incrementAdminYearlyTestActivityMonthStats(
                year: $year,
                month: $month,
                now: $now
            );
        });
    }

    private function incrementUserYearlyTestStats(int $userId, int $year, CarbonImmutable $publishedAt, mixed $now): void
    {
        DB::statement(
            "
            INSERT INTO user_yearly_test_stats (
                user_id,
                year,
                total_likes_received,
                total_reviews_received,
                total_bookmarks_received,
                published_tests_count,
                first_published_test_at,
                last_published_test_at,
                created_at,
                updated_at
            )
            VALUES (?, ?, 0, 0, 0, 1, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                published_tests_count = published_tests_count + 1,
                first_published_test_at = COALESCE(first_published_test_at, VALUES(first_published_test_at)),
                last_published_test_at = VALUES(last_published_test_at),
                updated_at = VALUES(updated_at)
            ",
            [
                $userId,
                $year,
                $publishedAt,
                $publishedAt,
                $now,
                $now,
            ]
        );
    }

    private function incrementUserProfileStats(int $userId, mixed $now): void
    {
        DB::statement(
            "
            INSERT INTO user_profile_stats (
                user_id,
                followers_count,
                following_count,
                published_tests_count,
                library_materials_count,
                folders_count,
                average_test_rating,
                total_test_likes_received,
                total_test_reviews_received,
                total_test_bookmarks_received,
                created_at,
                updated_at
            )
            VALUES (?, 0, 0, 1, 0, 0, 0, 0, 0, 0, ?, ?)
            ON DUPLICATE KEY UPDATE
                published_tests_count = published_tests_count + 1,
                updated_at = VALUES(updated_at)
            ",
            [
                $userId,
                $now,
                $now,
            ]
        );
    }

    private function incrementAdminYearlyTestActivityMonthStats(
        int $year,
        int $month,
        mixed $now
    ): void {
        DB::statement(
            "
            INSERT INTO admin_yearly_test_activity_month_stats (
                year,
                month_no,
                published_tests_count,
                likes_count,
                reviews_count,
                downloads_count,
                created_at,
                updated_at
            )
            VALUES (?, ?, 1, 0, 0, 0, ?, ?)
            ON DUPLICATE KEY UPDATE
                published_tests_count = published_tests_count + 1,
                updated_at = VALUES(updated_at)
            ",
            [
                $year,
                $month,
                $now,
                $now,
            ]
        );
    }

    public function failed(TestApproved $event, Throwable $exception): void
    {
        Log::channel('errors')->error('update_published_test_summary_stats_failed', [
            'test_id' => $event->testId,
            'creator_user_id' => $event->creatorUserId,
            'message' => $exception->getMessage(),
            'exception' => $exception::class,
        ]);
    }
}
