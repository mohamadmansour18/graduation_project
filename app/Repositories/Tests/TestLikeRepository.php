<?php

namespace App\Repositories\Tests;

use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Models\Test;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TestLikeRepository
{
    public function findTest(int $testId): Builder|Model|null
    {
        return Test::query()->find($testId);
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

}
