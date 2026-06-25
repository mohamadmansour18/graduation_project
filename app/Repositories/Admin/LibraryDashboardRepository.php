<?php

namespace App\Repositories\Admin;

use App\Enums\Decision;
use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\VisibilityType;
use App\Models\LibraryMaterial;
use App\Models\LibraryMaterialReport;
use App\Models\LibraryMaterialReviewRound;
use App\Models\LibraryMaterialStatusHistory;
use App\Models\LibraryReportReasonCounter;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;

class LibraryDashboardRepository
{
    public function paginateApprovedMaterials(string $sortBy, int $perPage): CursorPaginator
    {
        $reviewStatus = match ($sortBy) {
            'new' => LibraryMaterialReviewStatus::New->value,
            'reported' => LibraryMaterialReviewStatus::Reported->value,
            default => LibraryMaterialReviewStatus::Approved->value,
        };

        $query = LibraryMaterial::query()
            ->select([
                'id',
                'creator_user_id',
                'title',
                'description',
                'content_kind',
                'visibility_type',
                'review_status',
                'published_at',
                'created_at',
                'like_count',
            ])
            ->with([
                'firstAsset:id,library_material_id,storage_path,position',
                'interests:id,name',
            ])
            ->where('review_status', $reviewStatus)
            ->where('visibility_type', VisibilityType::Public->value);

        match ($sortBy) {
            'new', 'approved', 'reported' => $query
                ->orderByDesc('created_at')
                ->orderByDesc('id'),

            'id' => $query->orderByDesc('id'),

            'type' => $query
                ->orderByRaw("CASE WHEN content_kind = 'صور مجمعة' THEN 0 ELSE 1 END")
                ->orderByDesc('id'),

            'most_liked' => $query
                ->orderByDesc('like_count')
                ->orderByDesc('id'),

            default => $query
                ->orderByDesc('published_at')
                ->orderByDesc('id'),
        };

        return $query->cursorPaginate($perPage);
    }

    public function getCurrentYearStatistics(): array
    {
        $year = now()->year;

        $stats = LibraryMaterial::query()
            ->whereYear('created_at', $year)
            ->where('review_status', LibraryMaterialReviewStatus::Approved->value)
            ->where('visibility_type', VisibilityType::Public->value)
            ->selectRaw('COUNT(*) as total_materials_count')
            ->selectRaw("SUM(CASE WHEN content_kind = 'ملف' THEN 1 ELSE 0 END) as total_files_count")
            ->selectRaw("SUM(CASE WHEN content_kind = 'صور مجمعة' THEN 1 ELSE 0 END) as total_images_count")
            ->first();

        return [
            'year' => $year,
            'total_materials_count' => (int) $stats->total_materials_count,
            'total_files_count' => (int) $stats->total_files_count,
            'total_images_count' => (int) $stats->total_images_count,
        ];
    }

    public function findMaterialDetailsOrFail(int $libraryMaterialId): LibraryMaterial
    {
        return LibraryMaterial::query()
            ->select([
                'id',
                'creator_user_id',
                'title',
                'description',
                'content_kind',
                'visibility_type',
                'target_level',
                'review_status',
                'asset_count',
                'like_count',
                'bookmarks_count',
                'download_count',
                'published_at',
                'created_at',
            ])
            ->with([
                'creatorUser:id,name,is_academically_verified',
                'creatorUser.userProfile:id,user_id,avatar_path,avatar_disk',
                'creatorUser.userProfileStat:id,user_id,followers_count,following_count',

                'interests:id,name',

                'libraryMaterialAssets:id,library_material_id,asset_type,storage_disk,storage_path,original_name,mime_type,position',
            ])
            ->findOrFail($libraryMaterialId);
    }

    public function findMaterialForReportsOrFail(int $libraryMaterialId): LibraryMaterial
    {
        return LibraryMaterial::query()
            ->select([
                'id',
                'title',
                'current_approval_version',
                'review_status',
            ])
            ->findOrFail($libraryMaterialId);
    }

    public function getReasonCountersForApprovalVersion(int $libraryMaterialId, int $approvalVersion): Collection
    {
        return LibraryReportReasonCounter::query()
            ->select([
                'reason',
                'reporters_count',
            ])
            ->where('library_material_id', $libraryMaterialId)
            ->where('approval_version', $approvalVersion)
            ->orderByDesc('reporters_count')
            ->get();
    }

    public function paginateReportsForApprovalVersion(int $libraryMaterialId, int $approvalVersion, int $perPage): CursorPaginator
    {
        return LibraryMaterialReport::query()
            ->select([
                'id',
                'library_material_id',
                'user_id',
                'approval_version',
                'reason',
                'description',
                'reported_at',
            ])
            ->with([
                'user:id,name,is_academically_verified',
                'user.userProfile:id,user_id,avatar_path,avatar_disk',
            ])
            ->where('library_material_id', $libraryMaterialId)
            ->where('approval_version', $approvalVersion)
            ->orderByDesc('reported_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }

    public function findMaterialOrFail(int $libraryMaterialId): LibraryMaterial
    {
        return LibraryMaterial::query()
            ->select([
                'id',
                'title',
                'review_status',
                'current_approval_version',
            ])
            ->findOrFail($libraryMaterialId);
    }

    public function getHistories(int $libraryMaterialId): Collection
    {
        return LibraryMaterialStatusHistory::query()
            ->select([
                'id',
                'library_material_id',
                'from_status',
                'to_status',
                'changed_by_user_id',
                'note',
                'created_at',
            ])
            ->with([
                'changedByUser:id,name,role_id',
                'changedByUser.role:id,name',
                'changedByUser.userProfile:id,user_id,avatar_path,avatar_disk',
            ])
            ->where('library_material_id', $libraryMaterialId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }


    public function findMaterialForUpdateOrFail(int $libraryMaterialId): LibraryMaterial
    {
        return LibraryMaterial::query()
            ->select([
                'id',
                'review_status',
                'current_approval_version',
                'published_at',
            ])
            ->whereKey($libraryMaterialId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function findOpenReviewRoundForUpdate(int $libraryMaterialId): ?LibraryMaterialReviewRound
    {
        return LibraryMaterialReviewRound::query()
            ->where('library_material_id', $libraryMaterialId)
            ->where('decision', Decision::Pending->value)
            ->orderByDesc('round_no')
            ->lockForUpdate()
            ->first();
    }

    public function approveOpenRound(LibraryMaterialReviewRound $round, int $reviewerUserId, CarbonInterface $decidedAt): void
    {
        $round->forceFill([
            'reviewer_user_id' => $reviewerUserId,
            'decision' => Decision::Approved->value,
            'decided_at' => $decidedAt,
        ])->save();
    }

    public function approveMaterial(LibraryMaterial $material, int $approvalVersion, CarbonInterface $publishedAt): void
    {
        $material->forceFill([
            'review_status' => LibraryMaterialReviewStatus::Approved->value,
            'current_approval_version' => $approvalVersion,
            'published_at' => $publishedAt,
        ])->save();
    }

    public function createStatusHistory(int $libraryMaterialId, string $fromStatus, string $toStatus, int $changedByUserId, string $note): LibraryMaterialStatusHistory
    {
        return LibraryMaterialStatusHistory::query()->create([
            'library_material_id' => $libraryMaterialId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_user_id' => $changedByUserId,
            'note' => $note,
        ]);
    }

    public function forceDeleteMaterial(LibraryMaterial $material): void
    {
        $material->forceDelete();
    }

}
