<?php

namespace App\Services\Tests;

use App\Events\TestLikeStateChanged;
use App\Exceptions\Api\TestException;
use App\Repositories\Tests\TestLikeRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class TestLikeService
{
    public function __construct(
        private readonly TestLikeRepository $testLikeRepository
    ) {}

    public function like(int $testId, int $userId): array
    {
        $eventPayload = null;

        $test = $this->testLikeRepository->findTest($testId);

        if(! $test){
            throw TestException::notAvailable();
        }

        if($test->creator_user_id === $userId)
        {
            throw TestException::cannotLikeOwnTest();
        }

        $result = DB::transaction(function () use ($testId, $userId, &$eventPayload , $test) {

            $created = $this->testLikeRepository->createLikeIfMissing(
                testId: $testId,
                userId: $userId,
            );

            if($created)
            {
                $this->testLikeRepository->incrementTestLikesCount($testId);

                $eventPayload = [
                    'test_id' => $testId,
                    'creator_user_id' => (int) $test->creator_user_id,
                    'actor_user_id' => $userId,
                    'delta' => 1,
                    'effective_at' => now(),
                ];
            }

            return [
                'has_liked' => true,
                'state_changed' => $created,
            ];

        });

        if($eventPayload !== null)
        {
            event(new TestLikeStateChanged(...$eventPayload));
        }

        return $result;
    }

    public function unlike(int $testId, int $userId): array
    {
        $eventPayload = null;

        $test = $this->testLikeRepository->findTest($testId);

        if(! $test){
            throw TestException::notFound();
        }

        if($test->creator_user_id === $userId)
        {
            throw TestException::cannotUnlikeOwnTest();
        }

        $result = DB::transaction(function () use ($testId, $userId, &$eventPayload , $test) {

            $originalLikeCreatedAt = $this->testLikeRepository->deleteLikeIfExists(
                testId: $testId,
                userId: $userId
            );

            if($originalLikeCreatedAt !== null)
            {
                $this->testLikeRepository->decrementTestLikesCount($testId);

                $eventPayload = [
                    'test_id' => $testId,
                    'creator_user_id' => (int) $test->creator_user_id,
                    'actor_user_id' => $userId,
                    'delta' => -1,
                    'effective_at' => $originalLikeCreatedAt,
                ];
            }

            return [
                'has_liked' => false,
                'state_changed' => $originalLikeCreatedAt !== null,
            ];
        });

        if ($eventPayload !== null) {
            event(new TestLikeStateChanged(...$eventPayload));
        }

        return $result;
    }

    public function listLikedUsers(int $testId, int $viewerId, ?string $search, int $perPage): CursorPaginator
    {
        $canSee = $this->testLikeRepository->canViewerSeeTestLikes($testId);

        if (! $canSee) {
            throw TestException::notAvailable();
        }

        return $this->testLikeRepository->cursorPaginateLikedUsers(
            testId: $testId,
            viewerId: $viewerId,
            search: $search,
            perPage: $perPage
        );
    }
}
