<?php

namespace App\Repositories\Library;

use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\VisibilityType;
use App\Models\LibraryMaterial;
use App\Models\LibraryMaterialLike;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class LibraryMaterialLikeRepository
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

    public function like(int $userId, int $materialId): bool
    {
        return DB::transaction(function () use ($userId, $materialId) {
            $inserted = LibraryMaterialLike::query()->insertOrIgnore([
                'library_material_id' => $materialId,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 1) {
                LibraryMaterial::query()
                    ->whereKey($materialId)
                    ->increment('like_count');
            }

            return $inserted === 1;
        });
    }

    public function unlike(int $userId, int $materialId): bool
    {
        return DB::transaction(function () use ($userId, $materialId) {
            $deleted = LibraryMaterialLike::query()
                ->where('library_material_id', $materialId)
                ->where('user_id', $userId)
                ->delete();

            if ($deleted > 0) {
                LibraryMaterial::query()
                    ->whereKey($materialId)
                    ->where('like_count', '>', 0)
                    ->decrement('like_count');
            }

            return $deleted > 0;
        });
    }

    public function canViewerSeeLibraryLikes(int $materialId): bool
    {
        return DB::table('library_material')
            ->where('id', $materialId)
            ->where('visibility_type', VisibilityType::Public->value)
            ->exists();
    }

    public function cursorPaginateLikedUsers(int $materialId, int $viewerId, ?string $search, int $perPage): CursorPaginator {

        return DB::table('library_material_likes')
            ->join('users', 'users.id', '=', 'library_material_likes.user_id')
            ->leftJoin('user_profile', 'user_profile.user_id', '=', 'users.id')
            ->leftJoin('user_onboarding_profiles', 'user_onboarding_profiles.user_id', '=', 'users.id')
            ->select([
                'users.id as user_id',
                'users.name',
                'users.is_academically_verified',
                'user_profile.avatar_path',
                'user_onboarding_profiles.education_level',
                'library_material_likes.id as like_id',
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
            ->where('library_material_likes.library_material_id', $materialId)
            ->where('library_material_likes.user_id' , '!=' , $viewerId)
            ->when($search !== null, function ($query) use ($search) {
                $query->where('users.name', 'like', $this->escapeLike($search) . '%');
            })
            ->orderByDesc('like_id')
            ->cursorPaginate($perPage);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );
    }
}
