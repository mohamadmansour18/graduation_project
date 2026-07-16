<?php

namespace App\Listeners;

use App\Events\TestReviewStateChanged;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateTestReviewSummaryTables implements ShouldQueue
{
    use InteractsWithQueue;
    public bool $afterCommit = true;
    public int $tries = 2;
    public array $backoff = [5, 10];
    public int $timeout = 60;
    public string $queue = 'light';
    public function handle(TestReviewStateChanged $event): void
    {
        $effectiveAt = $event->effective_at instanceof Carbon
            ? $event->effective_at
            : Carbon::parse($event->effective_at);

        DB::transaction(function () use ($event, $effectiveAt) {
            $year = (int) $effectiveAt->year;
            $month = (int) $effectiveAt->month;

            $this->ensureUserProfileStatsRow($event->creator_user_id);
            $this->ensureUserYearlyTestStatsRow($event->creator_user_id, $year);
            $this->ensureAdminYearlyTestActivityMonthStatsRow($year, $month);

            $this->applyDelta(
                table: 'user_profile_stats',
                where: ['user_id' => $event->creator_user_id],
                column: 'total_test_reviews_received',
                delta: $event->delta
            );

            $this->applyDelta(
                table: 'user_yearly_test_stats',
                where: [
                    'user_id' => $event->creator_user_id,
                    'year' => $year,
                ],
                column: 'total_reviews_received',
                delta: $event->delta
            );

            $this->applyDelta(
                table: 'admin_yearly_test_activity_month_stats',
                where: [
                    'year' => $year,
                    'month_no' => $month,
                ],
                column: 'reviews_count',
                delta: $event->delta
            );
        });
    }

    public function failed(TestReviewStateChanged $event, Throwable $exception): void
    {
        Log::channel('errors')->error('Failed processing test reviews summary update', [
            'action' => 'test_like_summary_update_failed',
            'test_id' => $event->test_id,
            'creator_user_id' => $event->creator_user_id,
            'actor_user_id' => $event->actor_user_id,
            'exception_message' => $exception->getMessage(),
            'job_id' => optional($this->job)->getJobId(),
        ]);
    }

    private function ensureUserProfileStatsRow(int $userId): void
    {
        $now = now();

        DB::table('user_profile_stats')->insertOrIgnore([
            'user_id' => $userId,
            'followers_count' => 0,
            'following_count' => 0,
            'published_tests_count' => 0,
            'library_materials_count' => 0,
            'folders_count' => 0,
            'average_test_rating' => 0,
            'total_test_likes_received' => 0,
            'total_test_reviews_received' => 0,
            'total_test_bookmarks_received' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureUserYearlyTestStatsRow(int $userId, int $year): void
    {
        $now = now();

        DB::table('user_yearly_test_stats')->insertOrIgnore([
            'user_id' => $userId,
            'year' => $year,
            'total_likes_received' => 0,
            'total_reviews_received' => 0,
            'total_bookmarks_received' => 0,
            'published_tests_count' => 0,
            'first_published_test_at' => null,
            'last_published_test_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureAdminYearlyTestActivityMonthStatsRow(int $year, int $month): void
    {
        $now = now();

        DB::table('admin_yearly_test_activity_month_stats')->insertOrIgnore([
            'year' => $year,
            'month_no' => $month,
            'published_tests_count' => 0,
            'likes_count' => 0,
            'reviews_count' => 0,
            'downloads_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function applyDelta(string $table, array $where, string $column, int $delta): void
    {
        $query = DB::table($table)->where($where);

        if ($delta > 0) {
            $query->increment($column, $delta, [
                'updated_at' => now(),
            ]);

            return;
        }

        $query
            ->where($column, '>', 0)
            ->decrement($column, abs($delta), [
                'updated_at' => now(),
            ]);
    }
}
