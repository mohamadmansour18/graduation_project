<?php

namespace App\Services\Tests;

use App\DTOs\Notifications\NotificationPayload;
use App\Events\TestLikeStateChanged;
use App\Exceptions\Api\TestException;
use App\Helpers\BuildActor;
use App\Repositories\Tests\TestLikeRepository;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class TestLikeService
{
    public function __construct(
        private readonly TestLikeRepository $testLikeRepository,
        private readonly NotificationCenter $notificationCenter,
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

            $this->sendTestLikedNotification($eventPayload);
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

    private function sendTestLikedNotification(array $eventPayload): void
    {

        $payload = NotificationPayload::make(
            title: 'عملية تسجيل اعجاب',
            body: 'قام المستخدم بتسجيل إعجابه بالاختبار الخاص بك',
            metadata: [
                'type' => 'test_liked',
                'category' => 'social',

                'presentation' => [
                    'mode' => 'user',
                    'floor_color' => null,
                    'icon' => null,
                ],

                'actor' => BuildActor::buildUserActor((int) $eventPayload['actor_user_id']),

                'navigation' => [
                    'screen' => 'my_test_details',
                    'action' => 'open',
                ],

                'params' => [
                    'test_id' => (int) $eventPayload['test_id'],
                    'actor_user_id' => (int) $eventPayload['actor_user_id'],
                ],

            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $eventPayload['creator_user_id'],
            payload: $payload,
        );
    }
}
