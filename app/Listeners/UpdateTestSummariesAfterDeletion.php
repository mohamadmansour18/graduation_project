<?php

namespace App\Listeners;

use App\Events\TestDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateTestSummariesAfterDeletion
{

    public function handle(TestDeleted $event): void
    {
        DB::transaction(function () use ($event) {
            $publishedYear = $event->publishedAt
                ? (int) date('Y', strtotime($event->publishedAt))
                : (int) now()->year;

            $publishedMonth = $event->publishedAt
                ? (int) date('n', strtotime($event->publishedAt))
                : (int) now()->month;

            $publishedDelta = $event->wasPublished ? 1 : 0;

            $this->updateUserProfileStats($event, $publishedDelta);

            $this->updateUserYearlyTestStats($event, $publishedYear, $publishedDelta);

            $this->updateUserYearlyPublishMonthStats(
                event: $event,
                year: $publishedYear,
                month: $publishedMonth,
                publishedDelta: $publishedDelta
            );

            $this->updateAdminYearlyTestActivityMonthStats(
                event: $event,
                year: $publishedYear,
                month: $publishedMonth,
                publishedDelta: $publishedDelta
            );
        });
    }

    private function updateUserProfileStats(TestDeleted $event, int $publishedDelta): void
    {
        $row = DB::table('user_profile_stats')
            ->where('user_id', $event->creatorUserId)
            ->lockForUpdate()
            ->first();

        if (! $row) {
            return;
        }

        $oldPublishedCount = (int) $row->published_tests_count;
        $newPublishedCount = max(0, $oldPublishedCount - $publishedDelta);

        $newAverageRating = (float) $row->average_test_rating;

        if ($publishedDelta === 1 && $oldPublishedCount > 0) {
            $newAverageRating = $newPublishedCount > 0
                ? (((float) $row->average_test_rating * $oldPublishedCount) - $event->averageRating) / $newPublishedCount
                : 0;
        }

        DB::table('user_profile_stats')
            ->where('user_id', $event->creatorUserId)
            ->update([
                'published_tests_count' => $newPublishedCount,
                'average_test_rating' => round(max(0, $newAverageRating), 2),
                'total_test_likes_received' => DB::raw('GREATEST(total_test_likes_received - '.$event->likesCount.', 0)'),
                'total_test_bookmarks_received' => DB::raw('GREATEST(total_test_bookmarks_received - '.$event->bookmarksCount.', 0)'),
                'total_test_reviews_received' => DB::raw('GREATEST(total_test_reviews_received - '.$event->reviewsCount.', 0)'),
                'updated_at' => now(),
            ]);
    }

    private function updateUserYearlyTestStats(TestDeleted $event, int $year, int $publishedDelta): void
    {
        DB::table('user_yearly_test_stats')
            ->where('user_id', $event->creatorUserId)
            ->where('year', $year)
            ->update([
                'total_likes_received' => DB::raw('GREATEST(total_likes_received - '.$event->likesCount.', 0)'),
                'total_reviews_received' => DB::raw('GREATEST(total_reviews_received - '.$event->reviewsCount.', 0)'),
                'total_bookmarks_received' => DB::raw('GREATEST(total_bookmarks_received - '.$event->bookmarksCount.', 0)'),
                'published_tests_count' => DB::raw('GREATEST(published_tests_count - '.$publishedDelta.', 0)'),
                'updated_at' => now(),
            ]);
    }

    private function updateUserYearlyPublishMonthStats(
        TestDeleted $event,
        int $year,
        int $month,
        int $publishedDelta
    ): void {
        if ($publishedDelta === 0) {
            return;
        }

        DB::table('user_yearly_test_publish_month_stats')
            ->where('user_id', $event->creatorUserId)
            ->where('year', $year)
            ->where('month_no', $month)
            ->update([
                'published_tests_count' => DB::raw('GREATEST(published_tests_count - 1, 0)'),
                'updated_at' => now(),
            ]);
    }

    private function updateAdminYearlyTestActivityMonthStats(
        TestDeleted $event,
        int $year,
        int $month,
        int $publishedDelta
    ): void {
        DB::table('admin_yearly_test_activity_month_stats')
            ->where('year', $year)
            ->where('month_no', $month)
            ->update([
                'published_tests_count' => DB::raw('GREATEST(published_tests_count - '.$publishedDelta.', 0)'),
                'likes_count' => DB::raw('GREATEST(likes_count - '.$event->likesCount.', 0)'),
                'reviews_count' => DB::raw('GREATEST(reviews_count - '.$event->reviewsCount.', 0)'),
                'downloads_count' => DB::raw('GREATEST(downloads_count - '.$event->downloadsCount.', 0)'),
                'updated_at' => now(),
            ]);
    }

    public function failed(TestDeleted $event, \Throwable $exception): void
    {
        Log::channel('errors')->error('failed_to_update_test_summaries_after_deletion', [
            'test_id' => $event->testId,
            'creator_user_id' => $event->creatorUserId,
            'message' => $exception->getMessage(),
        ]);
    }
}
