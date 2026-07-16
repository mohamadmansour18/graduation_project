<?php

namespace App\Listeners;

use App\Events\LibraryMaterialDeletedByOwner;
use App\Events\LibraryMaterialLikeChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateAdminLibraryOnMaterialDeleted implements ShouldQueue
{
    use InteractsWithQueue;
    public bool $afterCommit = true;
    public int $tries = 2;
    public array $backoff = [5, 10];
    public int $timeout = 60;
    public string $queue = 'light';
    public function handle(LibraryMaterialDeletedByOwner $event): void
    {
        $year = (int) $event->materialCreatedAt->year;
        $monthNo = (int) $event->materialCreatedAt->month;

        DB::table('admin_yearly_library_material_activity_month_stats')
            ->where('year', $year)
            ->where('month_no', $monthNo)
            ->update([
                'published_materials_count' => DB::raw('GREATEST(published_materials_count - 1, 0)'),
                'likes_count' => DB::raw('GREATEST(likes_count - ' . $event->materialLikesCount . ', 0)'),
                'updated_at' => now(),
            ]);
    }

    public function failed(LibraryMaterialDeletedByOwner $event, Throwable $exception): void
    {
        Log::channel('errors')->error('Failed processing library material summary update count', [
            'materialId' => $event->materialId,
            'exception_message' => $exception->getMessage(),
            'job_id' => optional($this->job)->getJobId(),
        ]);
    }
}
