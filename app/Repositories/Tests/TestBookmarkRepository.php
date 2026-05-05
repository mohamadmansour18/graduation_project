<?php

namespace App\Repositories\Tests;

use App\Models\Test;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TestBookmarkRepository
{
    public function findTest(int $testId): Builder|Model|null
    {
        return Test::query()->find($testId);
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

}
