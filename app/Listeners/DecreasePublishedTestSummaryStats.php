<?php

namespace App\Listeners;

use App\Events\TestDashboardDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DecreasePublishedTestSummaryStats implements ShouldQueue
{
    use InteractsWithQueue;

    public bool $afterCommit = true;

    public int $tries = 2;

    public function handle(TestDashboardDeleted $event): void
    {
        $now = now();

        if (! $event->shouldDecreasePublishCounters) {
            return;
        }

        if (! $event->publishedYear || ! $event->publishedMonth) {
            return;
        }

        DB::transaction(function () use ($event , $now) {
            $this->decrementUserYearlyTestStats(
                userId: $event->creatorUserId,
                year: $event->publishedYear,
                now: $now
            );

            $this->decrementUserProfileStats(
                userId: $event->creatorUserId,
                now: $now
            );

            $this->decrementAdminYearlyTestActivityMonthStats(
                year: $event->publishedYear,
                month: $event->publishedMonth,
                now: $now
            );

            $this->decrementUserYearlyTestPublishMonthStats(
                userId: $event->creatorUserId,
                year: $event->publishedYear,
                month: $event->publishedMonth,
                now: $now
            );
        });
    }

    private function decrementUserYearlyTestStats(int $userId, int $year, mixed $now): void
    {
        DB::table('user_yearly_test_stats')
            ->where('user_id', $userId)
            ->where('year', $year)
            ->update([
                'published_tests_count' => DB::raw('GREATEST(published_tests_count - 1, 0)'),
                'updated_at' => $now,
            ]);
    }

    private function decrementUserProfileStats(int $userId, mixed $now): void
    {
        DB::table('user_profile_stats')
            ->where('user_id', $userId)
            ->update([
                'published_tests_count' => DB::raw('GREATEST(published_tests_count - 1, 0)'),
                'updated_at' => $now,
            ]);
    }

    private function decrementAdminYearlyTestActivityMonthStats(int $year, int $month, mixed $now): void
    {
        DB::table('admin_yearly_test_activity_month_stats')
            ->where('year', $year)
            ->where('month_no', $month)
            ->update([
                'published_tests_count' => DB::raw('GREATEST(published_tests_count - 1, 0)'),
                'updated_at' => $now,
            ]);
    }

    private function decrementUserYearlyTestPublishMonthStats(int $userId, int $year, int $month, mixed $now): void
    {
        DB::table('user_yearly_test_publish_month_stats')
            ->where('user_id', $userId)
            ->where('year', $year)
            ->where('month_no', $month)
            ->update([
                'published_tests_count' => DB::raw('GREATEST(published_tests_count - 1, 0)'),
                'updated_at' => $now,
            ]);
    }

    public function failed(TestDashboardDeleted $event, Throwable $exception): void
    {
        Log::channel('errors')->error('update_published_test_summary_stats_failed_for_delete', [
            'test_id' => $event->testId,
            'creator_user_id' => $event->creatorUserId,
            'message' => $exception->getMessage(),
            'exception' => $exception::class,
        ]);
    }
}
