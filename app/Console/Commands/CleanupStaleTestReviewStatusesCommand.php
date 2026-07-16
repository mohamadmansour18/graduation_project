<?php

namespace App\Console\Commands;

use App\Services\Tests\StaleTestReviewCleanupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CleanupStaleTestReviewStatusesCommand extends Command
{
    protected $signature = 'tests:cleanup-stale-review-statuses {--hours=48} {--limit=200}';

    protected $description = 'Delete public tests that stayed in stale review statuses for too long';

    public function handle(StaleTestReviewCleanupService $cleanupService): int
    {
        try {
            $hours = max(1, (int) $this->option('hours'));
            $limit = max(1, (int) $this->option('limit'));

            $summary = $cleanupService->handle(
                olderThanHours: $hours,
                limit: $limit,
            );

            $this->info('Stale test review status cleanup finished.');
            $this->table(
                ['checked', 'processed', 'soft_deleted', 'force_deleted', 'skipped', 'failed'],
                [[
                    $summary['checked'],
                    $summary['processed'],
                    $summary['soft_deleted'],
                    $summary['force_deleted'],
                    $summary['skipped'],
                    $summary['failed'],
                ]],
            );

            return $summary['failed'] > 0
                ? self::FAILURE
                : self::SUCCESS;
        } catch (Throwable $exception) {
            Log::channel('errors')->error('Stale test review status cleanup command failed', [
                'message' => $exception->getMessage(),
                'exception_class' => $exception::class,
            ]);

            throw $exception;
        }
    }
}
