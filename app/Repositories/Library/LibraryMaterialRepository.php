<?php

namespace App\Repositories\Library;

use App\Enums\Decision;
use App\Enums\LibraryDecision;
use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\LibraryTriggerType;
use App\Enums\SystemRole;
use App\Enums\VisibilityType;
use App\Models\LibraryMaterial;
use App\Models\LibraryMaterialAsset;
use App\Models\LibraryMaterialInterestSelection;
use App\Models\LibraryMaterialReviewRound;
use App\Models\LibraryMaterialStatusHistory;
use App\Models\Test;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LibraryMaterialRepository
{
    public function getFeaturedMaterials(int $userId, string $tab, int $limit = 6): array|Collection
    {
        return $this->basePublicApprovedQuery($userId)
            ->tap(fn (Builder $query) => $this->applyTabOrdering($query, $tab))
            ->limit($limit)
            ->get();
    }

    public function cursorPaginateMaterials(int $userId, string $tab, int $perPage, array $excludedIds = []): CursorPaginator
    {
        return $this->basePublicApprovedQuery($userId)
            ->when(!empty($excludedIds), fn (Builder $query) => $query->whereNotIn('id', $excludedIds))
            ->tap(fn (Builder $query) => $this->applyTabOrdering($query, $tab))
            ->cursorPaginate($perPage);
    }

    private function basePublicApprovedQuery(int $userId): Builder
    {
        return LibraryMaterial::query()
            ->select([
                'id',
                'creator_user_id',
                'title',
                'description',
                'content_kind',
                'visibility_type',
                'review_status',
                'published_at',
                'like_count',
                'bookmarks_count',
                'download_count',
            ])
            ->where('visibility_type', VisibilityType::Public->value)
            ->where('review_status', LibraryMaterialReviewStatus::Approved->value)
            ->where('creator_user_id', '!=', $userId)
            ->whereNotNull('published_at')
            ->with([
                'firstAsset:id,library_material_id,asset_type,storage_disk,storage_path,mime_type,position',
                'interests:id,name',
            ])
            ->withExists([
                'libraryMaterialBookmarks as viewer_has_bookmarked' => fn (Builder $query) =>
                $query->where('user_id', $userId),
            ]);
    }

    private function applyTabOrdering(Builder $query, string $tab): void
    {
        match ($tab) {
            'newest' => $query
                ->orderByDesc('published_at')
                ->orderByDesc('id'),

            'most_downloaded' => $query
                ->orderByDesc('download_count')
                ->orderByDesc('published_at')
                ->orderByDesc('id'),

            default => $query
                ->orderByDesc('like_count')
                ->orderByDesc('published_at')
                ->orderByDesc('id'),
        };
    }

    public function searchMaterials(int $userId, string $query, string $mode = 'all_public', int $perPage = 10): CursorPaginator
    {
        $searchBuilder = LibraryMaterial::search($query);

        if ($mode === 'all_public') {
            $searchBuilder->query(fn (Builder $builder) =>
            $builder->where('visibility_type', VisibilityType::Public->value)
                ->where('review_status', LibraryMaterialReviewStatus::Approved->value)
                ->where('creator_user_id', '!=', $userId)
            );
        } elseif ($mode === 'user_owned') {
            $searchBuilder->query(fn (Builder $builder) =>
            $builder->where('creator_user_id', $userId)
            );
        }

        $searchIds = $searchBuilder->keys();

        if ($searchIds->isEmpty()) {
            return LibraryMaterial::query()
                ->whereIn('id', [0])
                ->cursorPaginate($perPage);
        }

        $ids = $searchIds->toArray();
        $idsString = implode(',', array_map('intval', $ids));

        $queryBuilder = LibraryMaterial::with(['firstAsset', 'interests'])
            ->select('library_material.*')
            ->selectRaw("FIELD(id, {$idsString}) as search_order")
            ->whereIn('id', $ids);

        if ($mode === 'all_public') {
            $queryBuilder
                ->where('visibility_type', VisibilityType::Public->value)
                ->where('review_status', LibraryMaterialReviewStatus::Approved->value)
                ->where('creator_user_id', '!=', $userId);
        } elseif ($mode === 'user_owned') {
            $queryBuilder->where('creator_user_id', $userId);
        }

        return $queryBuilder
            ->orderBy('search_order')
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }

    ////////////////////////////////////////////////////////////////////

    public function countPendingPublicMaterialsForUser(int $userId): int
    {
        return LibraryMaterial::query()
            ->where('creator_user_id', $userId)
            ->where('visibility_type', VisibilityType::Public->value)
            ->where('review_status', LibraryMaterialReviewStatus::New->value)
            ->count();
    }

    public function createWithRelations(array $materialData, array $assetRows, array $interestIds, bool $shouldCreateReviewRound, int $changedByUserId): LibraryMaterial
    {
        return DB::transaction(function () use ($materialData, $assetRows, $interestIds, $shouldCreateReviewRound, $changedByUserId)
        {
            $material = LibraryMaterial::query()->create($materialData);

            foreach ($assetRows as $assetRow) {
                LibraryMaterialAsset::query()->create([
                    'library_material_id' => $material->id,
                    'asset_type' => $assetRow['asset_type'],
                    'storage_disk' => $assetRow['storage_disk'],
                    'storage_path' => $assetRow['storage_path'],
                    'original_name' => $assetRow['original_name'],
                    'mime_type' => $assetRow['mime_type'],
                    'position' => $assetRow['position'],
                ]);
            }

            foreach (array_values($interestIds) as $index => $interestId) {
                LibraryMaterialInterestSelection::query()->create([
                    'library_material_id' => $material->id,
                    'interest_id' => $interestId,
                    'slot_no' => $index + 1,
                ]);
            }

            if ($shouldCreateReviewRound) {
                $round = LibraryMaterialReviewRound::query()->create([
                    'library_material_id' => $material->id,
                    'round_no' => 1,
                    'reviewer_user_id' => null,
                    'trigger_type' => LibraryTriggerType::Initial_Submission->value,
                    'decision' => LibraryDecision::Pending->value,
                    'based_on_approval_version' => 0,
                    'started_at' => now(),
                    'decided_at' => null,
                ]);

                LibraryMaterialStatusHistory::query()->create([
                    'library_material_id' => $material->id,
                    'from_status' => null,
                    'to_status' => LibraryMaterialReviewStatus::New->value,
                    'changed_by_user_id' => $changedByUserId,
                    'note' => 'تم إرسال هذا المحتوى العام للمراجعة',
                ]);
            }

            return $material;
        });
    }

    ////////////////////////////////////////////////////////////////////

    public function findPublicApprovedMaterialForOtherUser(int $viewerUserId, int $materialId): ?LibraryMaterial
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
                'published_at',
                'asset_count',
                'like_count',
                'bookmarks_count',
                'download_count',
            ])
            ->selectRaw(
                'exists (
                select 1
                from user_follows
                where user_follows.followed_user_id = library_material.creator_user_id
                and user_follows.follower_user_id = ?
            ) as viewer_is_following_creator',
                [$viewerUserId]
            )
            ->whereKey($materialId)
            ->where('visibility_type', VisibilityType::Public->value)
            ->where('review_status', LibraryMaterialReviewStatus::Approved->value)
            ->where('creator_user_id', '!=', $viewerUserId)
            ->with([
                'libraryMaterialAssets:id,library_material_id,asset_type,storage_disk,storage_path,position',
                'interests:id,name',
                'creatorUser:id,name,is_academically_verified',
                'creatorUser.userProfile:id,user_id,avatar_disk,avatar_path',
                'creatorUser.userProfileStat:id,user_id,followers_count,following_count,published_tests_count',
            ])
            ->withExists([
                'libraryMaterialLikes as viewer_has_liked' => fn (Builder $query) =>
                $query->where('user_id', $viewerUserId),

                'libraryMaterialBookmarks as viewer_has_bookmarked' => fn (Builder $query) =>
                $query->where('user_id', $viewerUserId),
            ])
            ->first();
    }

    ////////////////////////////////////////////////////////////////////

    public function findOwnedMaterialDetails(int $userId, int $materialId): ?LibraryMaterial
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
                'published_at',
                'asset_count',
                'like_count',
                'bookmarks_count',
                'download_count',
            ])
            ->whereKey($materialId)
            ->where('creator_user_id', $userId)
            ->with([
                'libraryMaterialAssets:id,library_material_id,asset_type,storage_disk,storage_path,position',
                'interests:id,name',
                'libraryMaterialStatusHistories:id,library_material_id,from_status,to_status,note,created_at',
            ])
            ->first();
    }

    ////////////////////////////////////////////////////////////////////

    public function findForOwnerDeleteWithLock(int $materialId): ?LibraryMaterial
    {
        return LibraryMaterial::query()
            ->select([
                'id',
                'creator_user_id',
                'created_at',
                'like_count',
                'visibility_type',
            ])
            ->with([
                'libraryMaterialAssets:id,library_material_id,storage_disk,storage_path',
            ])
            ->whereKey($materialId)
            ->lockForUpdate()
            ->first();
    }

    public function deleteUsingEloquent(LibraryMaterial $material): void
    {
        $material->delete();
    }

    ////////////////////////////////////////////////////////////////////

    public function findOwnedMaterialForUpdate(int $userId, int $materialId): ?LibraryMaterial
    {
        return LibraryMaterial::query()
            ->whereKey($materialId)
            ->where('creator_user_id', $userId)
            ->first();
    }

    public function updateOwnedMaterial(LibraryMaterial $material, array $data, bool $shouldConvertPrivateToPublic, int $changedByUserId): LibraryMaterial
    {
        return DB::transaction(function () use ($material, $data, $shouldConvertPrivateToPublic, $changedByUserId)
        {

            $material = LibraryMaterial::query()
                ->whereKey($material->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = $material->review_status->value;

            $updatableFields = [
                'title',
                'description',
                'target_level',
            ];

            foreach ($updatableFields as $field) {
                if (array_key_exists($field, $data)) {
                    $material->{$field} = $data[$field];
                }
            }

            if ($shouldConvertPrivateToPublic) {
                $material->visibility_type = VisibilityType::Public;
                $material->review_status = LibraryMaterialReviewStatus::New;
                $material->current_approval_version = 0;
                $material->published_at = null;
            }

            $material->save();

            if (array_key_exists('interest_ids', $data)) {
                $this->replaceMaterialInterests(
                    material: $material,
                    interestIds: $data['interest_ids']
                );
            }

            if ($shouldConvertPrivateToPublic) {
                $this->createPrivateToPublicReviewWorkflow(
                    material: $material,
                    fromStatus: $oldStatus,
                    changedByUserId: $changedByUserId
                );
            }

            return $material->refresh();
        });
    }

    private function replaceMaterialInterests(LibraryMaterial $material, array $interestIds): void
    {
        $material->libraryMaterialInterestSelections()->delete();

        foreach (array_values($interestIds) as $index => $interestId) {
            $material->libraryMaterialInterestSelections()->create([
                'interest_id' => $interestId,
                'slot_no' => $index + 1,
            ]);
        }

        $materialId = $material->id;

        DB::afterCommit(function () use ($materialId) {
            Test::query()
                ->whereKey($materialId)
                ->first()
                ?->searchable();
        });
    }

    private function createPrivateToPublicReviewWorkflow(LibraryMaterial $material, string $fromStatus, int $changedByUserId): void
    {
        $lastRoundNo = LibraryMaterialReviewRound::query()
            ->where('library_material_id', $material->id)
            ->max('round_no');

        LibraryMaterialReviewRound::query()->create([
            'library_material_id' => $material->id,
            'round_no' => ((int) $lastRoundNo) + 1,
            'reviewer_user_id' => null,
            'trigger_type' => LibraryTriggerType::Initial_Submission->value,
            'decision' => LibraryDecision::Pending->value,
            'based_on_approval_version' => 0,
            'started_at' => now(),
            'decided_at' => null,
        ]);

        LibraryMaterialStatusHistory::query()->create([
            'library_material_id' => $material->id,
            'from_status' => null,
            'to_status' => LibraryMaterialReviewStatus::New->value,
            'changed_by_user_id' => $changedByUserId,
            'note' => 'تم تحويل المحتوى من خاص إلى عام',
        ]);
    }

    ////////////////////////////////////////////////////////////////////

    public function cursorPaginateSimilarMaterials(int $userId, array $interestIds, int $excludeMaterialId, int $perPage): CursorPaginator
    {
        return LibraryMaterial::query()
            ->select([
                'id',
                'creator_user_id',
                'title',
                'description',
                'content_kind',
                'visibility_type',
                'review_status',
                'published_at',
                'like_count',
            ])
            ->where('visibility_type', VisibilityType::Public->value)
            ->where('review_status', LibraryMaterialReviewStatus::Approved->value)
            ->where('creator_user_id', '!=', $userId)
            ->whereKeyNot($excludeMaterialId)
            ->whereHas('libraryMaterialInterestSelections', fn (Builder $query) =>
                $query->whereIn('interest_id', $interestIds)
            )
            ->with([
                'firstAsset:id,library_material_id,asset_type,storage_disk,storage_path,mime_type,position',
                'interests:id,name',
            ])
            ->withExists([
                'libraryMaterialBookmarks as viewer_has_bookmarked' => fn (Builder $query) =>
                $query->where('user_id', $userId),
            ])
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }

    public function getDashboardContentReviewerUserIds(): array
    {
        return User::query()
            ->whereHas('role', function ($query) {
                $query->whereIn('name', [SystemRole::Owner->value , SystemRole::Supervisor->value]);
            })
            ->pluck('id')
            ->all();
    }
}
