<?php

namespace App\Repositories\Library;

use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\VisibilityType;
use App\Models\LibraryMaterial;
use App\Models\LibraryMaterialBookmark;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class LibraryMaterialBookmarkRepository
{
    public function existsPublicApprovedForOtherUser(int $userId, int $materialId): bool
    {
        return LibraryMaterial::query()
            ->whereKey($materialId)
            ->where('visibility_type', VisibilityType::Public->value)
            ->where('review_status', LibraryMaterialReviewStatus::Approved->value)
            ->where('creator_user_id', '!=', $userId)
            ->exists();
    }
    public function bookmark(int $userId, int $materialId): bool
    {
        return DB::transaction(function () use ($userId, $materialId) {
            $inserted = LibraryMaterialBookmark::query()->insertOrIgnore([
                'library_material_id' => $materialId,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 1) {
                LibraryMaterial::query()
                    ->whereKey($materialId)
                    ->increment('bookmarks_count');
            }

            return $inserted === 1;
        });
    }
    public function unbookmark(int $userId, int $materialId): bool
    {
        return DB::transaction(function () use ($userId, $materialId) {
            $deleted = LibraryMaterialBookmark::query()
                ->where('library_material_id', $materialId)
                ->where('user_id', $userId)
                ->delete();

            if ($deleted > 0) {
                LibraryMaterial::query()
                    ->whereKey($materialId)
                    ->where('bookmarks_count', '>', 0)
                    ->decrement('bookmarks_count');
            }

            return $deleted > 0;
        });
    }

    public function canViewerSeeMaterialBookmarks(int $materialId): bool
    {
        return DB::table('library_material')
            ->where('id', $materialId)
            ->where('visibility_type', VisibilityType::Public->value)
            ->exists();
    }

    public function cursorPaginateBookmarkedUsers(int $materialId, int $viewerId, ?string $search, int $perPage): CursorPaginator
    {
        return DB::table('library_material_bookmarks')
            ->join('users', 'users.id', '=', 'library_material_bookmarks.user_id')
            ->leftJoin('user_profile', 'user_profile.user_id', '=', 'users.id')
            ->leftJoin('user_onboarding_profiles', 'user_onboarding_profiles.user_id', '=', 'users.id')
            ->select([
                'users.id as user_id',
                'users.name',
                'users.is_academically_verified',
                'user_profile.avatar_path',
                'user_onboarding_profiles.education_level',
                'library_material_bookmarks.id as bookmark_id',
            ])
            ->selectRaw(
                'exists (
                    select 1
                    from user_follows
                    where user_follows.follower_user_id = ?
                    and user_follows.followed_user_id = users.id
                ) as viewer_is_following',
                [$viewerId]
            )
            ->where('library_material_bookmarks.library_material_id', $materialId)
            ->where('library_material_bookmarks.user_id' , '!=' , $viewerId)
            ->when($search !== null, function ($query) use ($search) {
                $query->where('users.name', 'like', $this->escapeBookmark($search) . '%');
            })
            ->orderByDesc('bookmark_id')
            ->cursorPaginate($perPage);
    }

    private function escapeBookmark(string $value): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );
    }
}
