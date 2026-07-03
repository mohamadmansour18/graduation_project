<?php

namespace App\Services\LibraryMaterial;

use App\DTOs\Notifications\NotificationPayload;
use App\Exceptions\Api\LibraryMaterialException;
use App\Helpers\BuildActor;
use App\Repositories\Library\LibraryMaterialBookmarkRepository;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Contracts\Pagination\CursorPaginator;


class LibraryMaterialBookmarkService
{
    public function __construct(
        private readonly LibraryMaterialBookmarkRepository $bookmarkRepository,
        private readonly NotificationCenter $notificationCenter,
    )
    {}
    public function bookmark(int $userId, int $materialId): void
    {
        $this->ensureMaterialInteractable($userId, $materialId);

        $material = $this->bookmarkRepository->findMaterialNotificationSnapshot($materialId);

        if($material->creator_user_id === $userId)
        {
            throw LibraryMaterialException::cannotBookmarkOwnMaterial();
        }

        $created = $this->bookmarkRepository->bookmark($userId, $materialId);

        if ($created) {
            $this->sendMaterialBookmarkedNotification([
                'material_id' => (int) $material->id,
                'material_title' => $material->title,
                'owner_user_id' => (int) $material->creator_user_id,
                'actor_user_id' => $userId,
            ]);
        }
    }

    public function unbookmark(int $userId, int $materialId): void
    {
        $this->ensureMaterialInteractable($userId, $materialId);

        $this->bookmarkRepository->unbookmark($userId, $materialId);
    }

    private function ensureMaterialInteractable(int $userId, int $materialId): void
    {
        if (! $this->bookmarkRepository->existsPublicApprovedForOtherUser($userId, $materialId)) {
            throw LibraryMaterialException::materialNotAvailableForInteraction();
        }
    }

    public function listBookmarkedUsers(int $materialId, int $viewerId, ?string $search, int $perPage): CursorPaginator
    {
        $canSee = $this->bookmarkRepository->canViewerSeeMaterialBookmarks($materialId);

        if (! $canSee) {
            throw LibraryMaterialException::notAvailable();
        }

        return $this->bookmarkRepository->cursorPaginateBookmarkedUsers(
            materialId: $materialId,
            viewerId: $viewerId,
            search: $search,
            perPage: $perPage
        );
    }

    private function sendMaterialBookmarkedNotification(array $data): void
    {
        $payload = NotificationPayload::make(
            title: 'عملية حفظ محتوى',
            body: 'قام بتسجيل حفظه لللمحتوى الخاص بك',
            metadata: [
                'type' => 'library_material_bookmarked',
                'category' => 'social',

                'presentation' => [
                    'mode' => 'user',
                    'floor_color' => null,
                    'icon' => null,
                ],

                'actor' => BuildActor::buildUserActor((int) $data['actor_user_id']),

                'navigation' => [
                    'screen' => 'my_library_material_details',
                    'action' => 'open',
                ],

                'params' => [
                    'material_id' => (int) $data['material_id'],
                    'actor_user_id' => (int) $data['actor_user_id'],
                ],
            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $data['owner_user_id'],
            payload: $payload,
        );
    }
}
