<?php

namespace App\Listeners;

use App\Events\LibraryMaterialLikeChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateAdminLibraryMaterialMonthlyStats implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 2;

    public function handle(LibraryMaterialLikeChanged $event): void
    {
        $year = (int) $event->occurredAt->year;
        $month = (int) $event->occurredAt->month;

        DB::transaction(function () use ($year, $month, $event) {
            DB::table('admin_yearly_library_material_activity_month_stats')
                ->updateOrInsert(
                    [
                        'year' => $year,
                        'month_no' => $month,
                    ],
                    [
                        'published_materials_count' => DB::raw('COALESCE(published_materials_count, 0)'),
                        'likes_count' => DB::raw('COALESCE(likes_count, 0)'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

            if ($event->delta > 0) {
                DB::table('admin_yearly_library_material_activity_month_stats')
                    ->where('year', $year)
                    ->where('month_no', $month)
                    ->increment('likes_count');
            } else {
                DB::table('admin_yearly_library_material_activity_month_stats')
                    ->where('year', $year)
                    ->where('month_no', $month)
                    ->update([
                        'likes_count' => DB::raw('GREATEST(likes_count - 1, 0)'),
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function failed(LibraryMaterialLikeChanged $event, Throwable $exception): void
    {
        Log::channel('errors')->error('Failed processing library material like summary update', [
            'libraryMaterialId' => $event->libraryMaterialId,
            'exception_message' => $exception->getMessage(),
            'job_id' => optional($this->job)->getJobId(),
        ]);
    }
}
