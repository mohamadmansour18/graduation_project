<?php

namespace App\Services\Tests;

use App\DTOs\Notifications\NotificationPayload;
use App\Events\TestBookmarkStateChanged;
use App\Exceptions\Api\TestException;
use App\Helpers\BuildActor;
use App\Repositories\Tests\TestBookmarkRepository;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class TestBookmarkService
{
    public function __construct(
        private readonly TestBookmarkRepository $testBookmarkRepository,
        private readonly NotificationCenter $notificationCenter,
    ) {}

    public function bookmark(int $testId, int $userId): array
    {
        $eventPayload = null;

        $test = $this->testBookmarkRepository->findTest($testId);

        if (! $test) {
            throw TestException::notAvailable();
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

            $this->sendTestBookmarkedNotification($eventPayload);
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

    public function listBookmarkedUsers(int $testId, int $viewerId, ?string $search, int $perPage): CursorPaginator
    {
        $canSee = $this->testBookmarkRepository->canViewerSeeTestBookmarks($testId);

        if (! $canSee) {
            throw TestException::notAvailable();
        }

        return $this->testBookmarkRepository->cursorPaginateBookmarkedUsers(
            testId: $testId,
            viewerId: $viewerId,
            search: $search,
            perPage: $perPage
        );
    }

    private function sendTestBookmarkedNotification(array $eventPayload): void
    {
        $payload = NotificationPayload::make(
            title: 'عملية حفظ اختبار',
            body: 'قام بحفظ بالاختبار الخاص بك',
            metadata: [
                'type' => 'test_bookmarked',
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
