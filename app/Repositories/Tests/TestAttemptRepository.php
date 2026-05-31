<?php

namespace App\Repositories\Tests;

use App\Enums\TestType;
use App\Models\Test;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TestAttemptRepository
{
    public function findTestForAttemptWithLock(int $testId): Builder|Test|null
    {
        return Test::query()
            ->whereKey($testId)
            ->where('test_type' , TestType::Public->value)
            ->lockForUpdate()
            ->first();
    }

    public function userHasAnyAttempt(int $testId, int $userId): bool
    {
        return DB::table('test_attempts')
            ->where('test_id', $testId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function findAttempt(int $testId, int $userId, string $mode): ?object
    {
        return DB::table('test_attempts')
            ->where('test_id', $testId)
            ->where('user_id', $userId)
            ->where('mode', $mode)
            ->first();
    }

    public function createAttempt(int $testId, int $userId, string $mode): void
    {
        DB::table('test_attempts')->insert([
            'test_id' => $testId,
            'user_id' => $userId,
            'mode' => $mode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function touchAttempt(int $testId, int $userId, string $mode): void
    {
        DB::table('test_attempts')
            ->where('test_id', $testId)
            ->where('user_id', $userId)
            ->where('mode', $mode)
            ->update([
                'updated_at' => now(),
            ]);
    }
}
