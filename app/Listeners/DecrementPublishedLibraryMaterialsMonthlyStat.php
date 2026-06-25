<?php

namespace App\Listeners;

use App\Events\LibraryMaterialFirstApproved;
use App\Events\LibraryMaterialPublishedDeleted;
use App\Models\AdminYearlyLibraryMaterialActivityMonthStat;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DecrementPublishedLibraryMaterialsMonthlyStat implements ShouldQueue
{
    use InteractsWithQueue;

    public bool $afterCommit = true;

    public int $tries = 2;

    public function handle(LibraryMaterialPublishedDeleted $event): void
    {
        DB::transaction(function () use ($event) {
            $publishedAt = $event->publishedAt;

            AdminYearlyLibraryMaterialActivityMonthStat::query()
                ->where('year', (int) $publishedAt->year)
                ->where('month_no', (int) $publishedAt->month)
                ->where('published_materials_count', '>', 0)
                ->update([
                    'published_materials_count' => DB::raw('published_materials_count - 1'),
                    'updated_at' => now(),
                ]);
        });
    }

    public function failed(LibraryMaterialFirstApproved $event, Throwable $exception): void
    {
        Log::channel('errors')->error('update_published_material_summary_stats_failed_for_insert', [
            'library_material_id' => $event->libraryMaterialId,
            'message' => $exception->getMessage(),
            'exception' => $exception::class,
        ]);
    }
}
