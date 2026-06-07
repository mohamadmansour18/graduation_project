<?php

namespace App\Repositories\Library;

use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\VisibilityType;
use App\Models\LibraryMaterial;

class LibraryMaterialShareRepository
{
    public function findShareDataById(int $materialId): ?LibraryMaterial
    {
        return LibraryMaterial::query()
            ->select([
                'id',
                'creator_user_id',
                'visibility_type',
                'review_status',
                'share_slug',
            ])
            ->whereKey($materialId)
            ->where('visibility_type' , VisibilityType::Public->value)
            ->where('review_status' , LibraryMaterialReviewStatus::Approved->value)
            ->lockForUpdate()
            ->first();
    }

    public function findByShareSlug(string $slug): ?LibraryMaterial
    {
        return LibraryMaterial::query()
            ->select([
                'id',
                'creator_user_id',
                'visibility_type',
                'review_status',
                'share_slug',
            ])
            ->where('share_slug', $slug)
            ->where('visibility_type' , VisibilityType::Public->value)
            ->where('review_status' , LibraryMaterialReviewStatus::Approved->value)
            ->first();
    }

    public function setShareSlug(LibraryMaterial $material, string $slug): void
    {
        $material->forceFill([
            'share_slug' => $slug,
        ])->save();
    }

    public function slugExists(string $slug): bool
    {
        return LibraryMaterial::query()
            ->where('share_slug', $slug)
            ->exists();
    }

}
