<?php

namespace App\Listeners;

use App\Events\TestDownloaded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateTestDownloadSummaryTables implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 2;

    public function handle(TestDownloaded $event): void
    {
        DB::transaction(function () use ($event) {
            $year = (int) $event->downloadedAt->year;
            $month = (int) $event->downloadedAt->month;

            $this->ensureAdminYearlyTestActivityMonthStatsRow($year, $month);

            DB::table('admin_yearly_test_activity_month_stats')
                ->where('year', $year)
                ->where('month_no', $month)
                ->increment('downloads_count', 1, [
                    'updated_at' => now(),
                ]);
        });
    }

    public function failed(TestDownloaded $event, Throwable $exception): void
    {
        Log::channel('errors')->error('Failed processing test download summary update', [
            'action' => 'test_download_summary_update_failed',
            'test_id' => $event->testId,
            'user_id' => $event->userId,
            'job_id' => optional($this->job)->getJobId(),
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
}
