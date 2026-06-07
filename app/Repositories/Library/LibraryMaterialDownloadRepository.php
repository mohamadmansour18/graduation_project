<?php

namespace App\Repositories\Library;

use App\Enums\VisibilityType;
use App\Models\LibraryMaterial;
use App\Models\LibraryMaterialDownloadLog;
use Illuminate\Support\Facades\DB;

class LibraryMaterialDownloadRepository
{
    public function findDownloadableMaterial(int $userId, int $materialId): ?LibraryMaterial
    {
        return LibraryMaterial::query()
            ->select([
                'id',
                'creator_user_id',
                'content_kind',
                'visibility_type',
                'review_status',
                'download_count',
            ])
            ->whereKey($materialId)
            ->where('id' , $materialId)
            ->with([
                'libraryMaterialAssets:id,library_material_id,asset_type,storage_disk,storage_path,original_name,mime_type,position',
            ])
            ->first();
    }

    public function recordDownloadOnce(int $userId, int $materialId): bool
    {
        return DB::transaction(function () use ($userId, $materialId) {
            $inserted = LibraryMaterialDownloadLog::query()->insertOrIgnore([
                'library_material_id' => $materialId,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 1) {
                LibraryMaterial::query()
                    ->whereKey($materialId)
                    ->increment('download_count');
            }

            return $inserted === 1;
        });
    }
}
