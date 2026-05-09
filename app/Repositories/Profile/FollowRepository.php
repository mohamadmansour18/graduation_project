<?php

namespace App\Repositories\Profile;

use Illuminate\Support\Facades\DB;

class FollowRepository
{
    public function userExists(int $userId): bool
    {
        return DB::table('users')
            ->where('id', $userId)
            ->exists();
    }

    public function createFollowIfMissing(int $followerUserId, int $followedUserId): bool
    {
        $now = now();

        $inserted = DB::table('user_follows')->insertOrIgnore([
            'follower_user_id' => $followerUserId,
            'followed_user_id' => $followedUserId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $inserted === 1;
    }

    public function deleteFollowIfExists(int $followerUserId, int $followedUserId): bool
    {
        $deleted = DB::table('user_follows')
            ->where('follower_user_id', $followerUserId)
            ->where('followed_user_id', $followedUserId)
            ->delete();

        return $deleted === 1;
    }

    public function ensureProfileStatsRow(int $userId): void
    {
        $now = now();

        DB::table('user_profile_stats')->insertOrIgnore([
            'user_id' => $userId,
            'followers_count' => 0,
            'following_count' => 0,
            'published_tests_count' => 0,
            'library_materials_count' => 0,
            'folders_count' => 0,
            'average_test_rating' => 0,
            'total_test_likes_received' => 0,
            'total_test_reviews_received' => 0,
            'total_test_bookmarks_received' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function incrementFollowersCount(int $userId): void
    {
        DB::table('user_profile_stats')
            ->where('user_id', $userId)
            ->increment('followers_count', 1, [
                'updated_at' => now(),
            ]);
    }

    public function incrementFollowingCount(int $userId): void
    {
        DB::table('user_profile_stats')
            ->where('user_id', $userId)
            ->increment('following_count', 1, [
                'updated_at' => now(),
            ]);
    }

    public function decrementFollowersCount(int $userId): void
    {
        DB::table('user_profile_stats')
            ->where('user_id', $userId)
            ->where('followers_count', '>', 0)
            ->decrement('followers_count', 1, [
                'updated_at' => now(),
            ]);
    }

    public function decrementFollowingCount(int $userId): void
    {
        DB::table('user_profile_stats')
            ->where('user_id', $userId)
            ->where('following_count', '>', 0)
            ->decrement('following_count', 1, [
                'updated_at' => now(),
            ]);
    }
}
