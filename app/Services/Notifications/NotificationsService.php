<?php

namespace App\Services\Notifications;

use App\Repositories\Notification\NotificationsRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;

class NotificationsService
{
    public function __construct(
        private readonly NotificationsRepository $repository,
    )
    {}

    public function listForUser(int $userId, int $perPage = 20, ?string $cursor = null,): CursorPaginator
    {
        return $this->repository->cursorPaginateForUser(
            userId: $userId,
            perPage: $perPage,
            cursor: $cursor,
        );
    }

    public function markAsRead(int $userId, array $notificationIds = [], bool $markAll = false,): array
    {
        $updatedCount = $this->repository->markAsReadForUser(
            userId: $userId,
            notificationIds: $notificationIds,
            markAll: $markAll,
        );

        return [
            'updated_count' => $updatedCount,
        ];
    }

    public function unreadCount(int $userId): array
    {
        $count = $this->repository->countUnreadForUser($userId);

        return [
            'unread_count' => $count,
            'has_unread' => $count > 0,
        ];
    }
}
