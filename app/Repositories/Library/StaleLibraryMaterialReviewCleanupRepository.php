<?php

namespace App\Repositories\Library;

use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\VisibilityType;
use App\Models\LibraryMaterial;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class StaleLibraryMaterialReviewCleanupRepository
{
    public function staleCandidateIds(CarbonInterface $cutoff, int $limit): array
    {
        $targetStatuses = [
            LibraryMaterialReviewStatus::New->value,
            LibraryMaterialReviewStatus::Reported->value,
        ];

        $latestStatusHistorySubQuery = DB::table('library_material_status_histories')
            ->select([
                'library_material_id',
                DB::raw('MAX(id) as latest_status_history_id'),
            ])
            ->groupBy('library_material_id');

        return DB::table('library_material')
            ->select('library_material.id')
            ->joinSub($latestStatusHistorySubQuery, 'latest_status_history', function ($join) {
                $join->on('latest_status_history.library_material_id', '=', 'library_material.id');
            })
            ->join('library_material_status_histories as current_status_history', function ($join) {
                $join->on(
                    'current_status_history.id',
                    '=',
                    'latest_status_history.latest_status_history_id',
                );
            })
            ->where('library_material.visibility_type', VisibilityType::Public->value)
            ->whereIn('library_material.review_status', $targetStatuses)
            ->whereIn('current_status_history.to_status', $targetStatuses)
            ->whereColumn('library_material.review_status', 'current_status_history.to_status')
            ->where('current_status_history.created_at', '<=', $cutoff)
            ->orderBy('current_status_history.created_at')
            ->orderBy('library_material.id')
            ->limit($limit)
            ->pluck('library_material.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function findCandidateForUpdate(int $materialId, CarbonInterface $cutoff): ?LibraryMaterial
    {
        $targetStatuses = [
            LibraryMaterialReviewStatus::New->value,
            LibraryMaterialReviewStatus::Reported->value,
        ];

        $latestStatusHistorySubQuery = DB::table('library_material_status_histories')
            ->select([
                'library_material_id',
                DB::raw('MAX(id) as latest_status_history_id'),
            ])
            ->groupBy('library_material_id');

        return LibraryMaterial::query()
            ->select([
                'library_material.*',
                'current_status_history.id as current_status_history_id',
                'current_status_history.created_at as current_status_changed_at',
            ])
            ->with([
                'libraryMaterialAssets:id,library_material_id,storage_disk,storage_path',
            ])
            ->joinSub($latestStatusHistorySubQuery, 'latest_status_history', function ($join) {
                $join->on('latest_status_history.library_material_id', '=', 'library_material.id');
            })
            ->join('library_material_status_histories as current_status_history', function ($join) {
                $join->on(
                    'current_status_history.id',
                    '=',
                    'latest_status_history.latest_status_history_id',
                );
            })
            ->whereKey($materialId)
            ->where('library_material.visibility_type', VisibilityType::Public->value)
            ->whereIn('library_material.review_status', $targetStatuses)
            ->whereIn('current_status_history.to_status', $targetStatuses)
            ->whereColumn('library_material.review_status', 'current_status_history.to_status')
            ->where('current_status_history.created_at', '<=', $cutoff)
            ->lockForUpdate()
            ->first();
    }

    public function forceDelete(LibraryMaterial $material): void
    {
        $material->forceDelete();
    }
}
