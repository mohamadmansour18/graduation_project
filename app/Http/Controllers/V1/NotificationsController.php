<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\ListNotificationsRequest;
use App\Http\Requests\Notifications\MarkNotificationsAsReadRequest;
use App\Http\Resources\NotificationResource;
use App\Services\Notifications\NotificationsService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class NotificationsController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly NotificationsService $service,
    )
    {}

    public function index(ListNotificationsRequest $request): JsonResponse
    {
        $paginator = $this->service->listForUser(
            userId: \Auth::id(),
            perPage: $request->perPage(),
            cursor: $request->cursorValue(),
        );

        return $this->dataResponse(
            data: [
                'notifications' => NotificationResource::collection(
                    collect($paginator->items())
                )->resolve($request),

                'meta' => [
                    'per_page' => $paginator->perPage(),
                    'next_cursor' => $paginator->nextCursor()?->encode(),
                    'prev_cursor' => $paginator->previousCursor()?->encode(),
                    'has_more_pages' => $paginator->hasMorePages(),
                ],
            ],
            title: 'تم جلب الإشعارات بنجاح'
        );
    }

    public function markAsRead(MarkNotificationsAsReadRequest $request): JsonResponse
    {
        $data = $this->service->markAsRead(
            userId: (int) auth('api')->id(),
            notificationIds: $request->notificationIds(),
            markAll: $request->markAll(),
        );

        return $this->dataResponse(
            data: $data,
            title: 'تم تعليم الإشعارات كمقروءة بنجاح'
        );
    }

    public function unreadCount(): JsonResponse
    {
        $data = $this->service->unreadCount(
            userId: (int) auth('api')->id()
        );

        return $this->dataResponse(
            data: $data,
            title: 'تم جلب عدد الإشعارات غير المقروءة بنجاح'
        );
    }

}
