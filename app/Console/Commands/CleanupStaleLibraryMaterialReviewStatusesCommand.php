<?php

namespace App\Console\Commands;

use App\Services\LibraryMaterial\StaleLibraryMaterialReviewCleanupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CleanupStaleLibraryMaterialReviewStatusesCommand extends Command
{
    protected $signature = 'library-materials:cleanup-stale-review-statuses {--hours=48} {--limit=200}';

    protected $description = 'Force delete public library materials that stayed in stale review statuses for too long';

    public function handle(StaleLibraryMaterialReviewCleanupService $cleanupService): int
    {
        try {
            $hours = max(1, (int) $this->option('hours'));
            $limit = max(1, (int) $this->option('limit'));

            $summary = $cleanupService->handle(
                olderThanHours: $hours,
                limit: $limit,
            );

            $this->info('Stale library material review status cleanup finished.');
            $this->table(
                ['checked', 'processed', 'force_deleted', 'skipped', 'failed'],
                [[
                    $summary['checked'],
                    $summary['processed'],
                    $summary['force_deleted'],
                    $summary['skipped'],
                    $summary['failed'],
                ]],
            );

            return $summary['failed'] > 0
                ? self::FAILURE
                : self::SUCCESS;
        } catch (Throwable $exception) {
            Log::channel('errors')->error('Stale library material review status cleanup command failed', [
                'message' => $exception->getMessage(),
                'exception_class' => $exception::class,
            ]);

            throw $exception;
        }
    }
}
