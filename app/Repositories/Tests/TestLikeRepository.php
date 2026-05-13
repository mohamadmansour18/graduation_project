<?php

namespace App\Repositories\Tests;

use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Models\Test;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TestLikeRepository
{
    public function findTest(int $testId): Builder|Model|null
    {
        return Test::query()
            ->select([
                'id',
                'creator_user_id',
                'test_type',
                'review_status',
                'likes_count',
            ])
            ->where('id', $testId)
            ->where('test_type', TestType::Public->value)
            ->where('review_status', TestReviewStatus::Approved->value)
            ->first();
    }

    public function createLikeIfMissing(int $testId, int $userId): bool
    {
        $inserted = DB::table('test_likes')->insertOrIgnore([
            'test_id' => $testId,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $inserted === 1;
    }

    public function deleteLikeIfExists(int $testId, int $userId): ?Carbon
    {
        $like = DB::table('test_likes')
            ->select(['id', 'created_at'])
            ->where('test_id', $testId)
            ->where('user_id', $userId)
            ->first();

        if (! $like) {
            return null;
        }

        $deleted = DB::table('test_likes')
            ->where('id', $like->id)
            ->delete();

        if ($deleted !== 1) {
            return null;
        }

        return Carbon::parse($like->created_at);
    }

    public function incrementTestLikesCount(int $testId): void
    {
        DB::table('test')
            ->where('id', $testId)
            ->increment('likes_count');
    }

    public function decrementTestLikesCount(int $testId): void
    {
        DB::table('test')
            ->where('id', $testId)
            ->where('likes_count', '>', 0)
            ->decrement('likes_count');
    }

    ///////////////////////////////////////////////////////////////////

    public function canViewerSeeTestLikes(int $testId): bool
    {
        return DB::table('test')
            ->where('id', $testId)
            ->where('test_type', TestType::Public->value)
            ->exists();
    }

    public function cursorPaginateLikedUsers(int $testId, int $viewerId, ?string $search, int $perPage): CursorPaginator {

        return DB::table('test_likes')
            ->join('users', 'users.id', '=', 'test_likes.user_id')
            ->leftJoin('user_profile', 'user_profile.user_id', '=', 'users.id')
            ->leftJoin('user_onboarding_profiles', 'user_onboarding_profiles.user_id', '=', 'users.id')
            ->select([
                'users.id as user_id',
                'users.name',
                'users.is_academically_verified',
                'user_profile.avatar_path',
                'user_onboarding_profiles.education_level',
                'test_likes.id as like_id',
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
            ->where('test_likes.test_id', $testId)
            ->where('test_likes.user_id' , '!=' , $viewerId)
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
