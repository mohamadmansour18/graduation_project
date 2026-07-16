<?php

namespace App\Listeners;

use App\Events\TestBookmarkStateChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateTestBookmarkSummaryTables implements ShouldQueue
{
    use InteractsWithQueue;

    public bool $afterCommit = true;
    public int $tries = 2;
    public array $backoff = [5, 10];
    public int $timeout = 60;
    public string $queue = 'light';
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TestBookmarkStateChanged $event): void
    {
        DB::transaction(function () use ($event) {
            $year = (int) $event->effective_at->year;

            $this->ensureUserProfileStatsRow($event->creator_user_id);
            $this->ensureUserYearlyTestStatsRow($event->creator_user_id, $year);

            $this->applyDelta(
                table: 'user_profile_stats',
                where: ['user_id' => $event->creator_user_id],
                column: 'total_test_bookmarks_received',
                delta: $event->delta
            );

            $this->applyDelta(
                table: 'user_yearly_test_stats',
                where: [
                    'user_id' => $event->creator_user_id,
                    'year' => $year,
                ],
                column: 'total_bookmarks_received',
                delta: $event->delta
            );
        });
    }

    public function failed(TestBookmarkStateChanged $event, Throwable $exception): void
    {
        Log::channel('errors')->error('Failed processing test bookmark summary update', [
            'action' => 'test_bookmark_summary_update_failed',
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
