<?php

namespace App\Repositories\Notification;

use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Notifications\DatabaseNotification;

class NotificationsRepository
{
    public function cursorPaginateForUser(int $userId, int $perPage = 20, ?string $cursor = null,): CursorPaginator
    {
        $notifiableType = (new User())->getMorphClass();

        return DatabaseNotification::query()
            ->select([
                'id',
                'type',
                'notifiable_type',
                'notifiable_id',
                'data',
                'read_at',
                'created_at',
            ])
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(
                perPage: $perPage,
                columns: ['*'],
                cursorName: 'cursor',
                cursor: $cursor
            );
    }

    public function markAsReadForUser(int $userId, array $notificationIds = [], bool $markAll = false,): int
    {
        $notifiableType = (new User())->getMorphClass();

        $query = DatabaseNotification::query()
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $userId)
            ->whereNull('read_at');

        if (! $markAll) {
            $query->whereIn('id', $notificationIds);
        }

        return $query->update([
            'read_at' => now(),
        ]);
    }

    public function countUnreadForUser(int $userId): int
    {
        $notifiableType = (new User())->getMorphClass();

        return DatabaseNotification::query()
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $userId)
            ->whereNull('read_at')
            ->count();
    }
}
