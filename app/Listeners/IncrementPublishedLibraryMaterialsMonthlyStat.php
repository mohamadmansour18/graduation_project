<?php

namespace App\Listeners;

use App\Events\LibraryMaterialFirstApproved;
use App\Models\AdminYearlyLibraryMaterialActivityMonthStat;
use DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class IncrementPublishedLibraryMaterialsMonthlyStat implements ShouldQueue
{

    use InteractsWithQueue;

    public bool $afterCommit = true;

    public int $tries = 2;

    public function handle(LibraryMaterialFirstApproved $event): void
    {
        $publishedAt = $event->publishedAt;

        AdminYearlyLibraryMaterialActivityMonthStat::query()->upsert(
            [
                [
                    'year' => (int) $publishedAt->year,
                    'month_no' => (int) $publishedAt->month,
                    'published_materials_count' => 1,
                    'likes_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            ['year', 'month_no'],
            [
                'published_materials_count' => DB::raw('published_materials_count + 1'),
                'updated_at' => now(),
            ]
        );
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
