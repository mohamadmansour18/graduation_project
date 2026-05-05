<?php

namespace App\Services\Tests;

use App\Events\TestBookmarkStateChanged;
use App\Exceptions\Api\TestException;
use App\Repositories\Tests\TestBookmarkRepository;
use Illuminate\Support\Facades\DB;

class TestBookmarkService
{
    public function __construct(
        private readonly TestBookmarkRepository $testBookmarkRepository
    ) {}

    public function bookmark(int $testId, int $userId): array
    {
        $eventPayload = null;

        $test = $this->testBookmarkRepository->findTest($testId);

        if (! $test) {
            throw TestException::notFound();
        }

        if($test->creator_user_id === $userId)
        {
            throw TestException::cannotBookmarkOwnTest();
        }

        $result = DB::transaction(function () use ($testId, $userId, &$eventPayload, $test) {

            $created = $this->testBookmarkRepository->createBookmarkIfMissing(
                testId: $testId,
                userId: $userId,
            );

            if ($created) {
                $this->testBookmarkRepository->incrementTestBookmarksCount($testId);

                $eventPayload = [
                    'test_id' => $testId,
                    'creator_user_id' => (int) $test->creator_user_id,
                    'actor_user_id' => $userId,
                    'delta' => 1,
                    'effective_at' => now(),
                ];
            }

            return [
                'has_bookmarked' => true,
                'state_changed' => $created,
            ];
        });

        if ($eventPayload !== null) {
            event(new TestBookmarkStateChanged(...$eventPayload));
        }

        return $result;
    }

    public function unbookmark(int $testId, int $userId): array
    {
        $eventPayload = null;

        $test = $this->testBookmarkRepository->findTest($testId);

        if (! $test) {
            throw TestException::notFound();
        }

        if($test->creator_user_id === $userId)
        {
            throw TestException::cannotUnbookmarkOwnTest();
        }

        $result = DB::transaction(function () use ($testId, $userId, &$eventPayload , $test) {


            $originalBookmarkCreatedAt = $this->testBookmarkRepository->deleteBookmarkIfExists(
                testId: $testId,
                userId: $userId
            );

            $deleted = $originalBookmarkCreatedAt !== null;

            if ($deleted) {
                $this->testBookmarkRepository->decrementTestBookmarksCount($testId);

                $eventPayload = [
                    'test_id' => $testId,
                    'creator_user_id' => (int) $test->creator_user_id,
                    'actor_user_id' => $userId,
                    'delta' => -1,
                    'effective_at' => $originalBookmarkCreatedAt,
                ];
            }

            return [
                'has_bookmarked' => false,
                'state_changed' => $deleted,
            ];
        });

        if ($eventPayload !== null) {
            event(new TestBookmarkStateChanged(...$eventPayload));
        }

        return $result;
    }
}
