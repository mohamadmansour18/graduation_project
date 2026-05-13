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

class TestBookmarkRepository
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

    public function createBookmarkIfMissing(int $testId, int $userId): bool
    {
        $inserted = DB::table('test_bookmarks')->insertOrIgnore([
            'test_id' => $testId,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $inserted === 1;
    }

    public function deleteBookmarkIfExists(int $testId, int $userId): ?Carbon
    {
        $bookmark = DB::table('test_bookmarks')
            ->select(['id', 'created_at'])
            ->where('test_id', $testId)
            ->where('user_id', $userId)
            ->first();

        if (! $bookmark) {
            return null;
        }

        $deleted = DB::table('test_bookmarks')
            ->where('id', $bookmark->id)
            ->delete();

        if ($deleted !== 1) {
            return null;
        }

        return Carbon::parse($bookmark->created_at);
    }

    public function incrementTestBookmarksCount(int $testId): void
    {
        DB::table('test')
            ->where('id', $testId)
            ->increment('bookmarks_count');
    }

    public function decrementTestBookmarksCount(int $testId): void
    {
        DB::table('test')
            ->where('id', $testId)
            ->where('bookmarks_count', '>', 0)
            ->decrement('bookmarks_count');
    }

    ///////////////////////////////////////////////////////////////////

    public function canViewerSeeTestBookmarks(int $testId): bool
    {
        return DB::table('test')
            ->where('id', $testId)
            ->where('test_type', TestType::Public->value)
            ->exists();
    }

    public function cursorPaginateBookmarkedUsers(int $testId, int $viewerId, ?string $search, int $perPage): CursorPaginator
    {
        return DB::table('test_bookmarks')
            ->join('users', 'users.id', '=', 'test_bookmarks.user_id')
            ->leftJoin('user_profile', 'user_profile.user_id', '=', 'users.id')
            ->leftJoin('user_onboarding_profiles', 'user_onboarding_profiles.user_id', '=', 'users.id')
            ->select([
                'users.id as user_id',
                'users.name',
                'users.is_academically_verified',
                'user_profile.avatar_path',
                'user_onboarding_profiles.education_level',
                'test_bookmarks.id as bookmark_id',
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
            ->where('test_bookmarks.test_id', $testId)
            ->where('test_bookmarks.user_id' , '!=' , $viewerId)
            ->when($search !== null, function ($query) use ($search) {
                $query->where('users.name', 'like', $this->escapeLike($search) . '%');
            })
            ->orderByDesc('bookmark_id')
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
