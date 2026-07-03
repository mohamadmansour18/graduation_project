<?php

namespace App\Services\LibraryMaterial;

use App\DTOs\Notifications\NotificationPayload;
use App\Events\LibraryMaterialLikeChanged;
use App\Exceptions\Api\LibraryMaterialException;
use App\Exceptions\Api\TestException;
use App\Helpers\BuildActor;
use App\Repositories\Library\LibraryMaterialLikeRepository;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Contracts\Pagination\CursorPaginator;

class LibraryMaterialLikeService
{
    public function __construct(
        private readonly LibraryMaterialLikeRepository $likeRepository,
        private readonly NotificationCenter $notificationCenter,
    )
    {}
    public function like(int $userId, int $materialId): void
    {
        $this->ensureMaterialInteractable($userId, $materialId);

        $material = $this->likeRepository->findMaterialNotificationSnapshot($materialId);

        if($material->creator_user_id === $userId)
        {
            throw LibraryMaterialException::cannotLikeOwnMaterial();
        }

        $created = $this->likeRepository->like($userId, $materialId);

        if ($created) {
            LibraryMaterialLikeChanged::dispatch($materialId , 1 , now() );

            $this->sendMaterialLikedNotification([
                'material_id' => (int) $material->id,
                'material_title' => $material->title,
                'owner_user_id' => (int) $material->creator_user_id,
                'actor_user_id' => $userId,
            ]);
        }
    }

    public function unlike(int $userId, int $materialId): void
    {
        $this->ensureMaterialInteractable($userId, $materialId);

        $deleted = $this->likeRepository->unlike($userId, $materialId);

        if ($deleted) {
            LibraryMaterialLikeChanged::dispatch($materialId, -1, now());
        }
    }

    private function ensureMaterialInteractable(int $userId, int $materialId): void
    {
        if (! $this->likeRepository->existsPublicApprovedForOtherUser($userId, $materialId)) {
            throw LibraryMaterialException::materialNotAvailableForInteraction();
        }
    }

    public function listLikedUsers(int $materialId, int $viewerId, ?string $search, int $perPage): CursorPaginator
    {
        $canSee = $this->likeRepository->canViewerSeeLibraryLikes($materialId);

        if (! $canSee) {
            throw LibraryMaterialException::notAvailable();
        }

        return $this->likeRepository->cursorPaginateLikedUsers(
            materialId: $materialId,
            viewerId: $viewerId,
            search: $search,
            perPage: $perPage
        );
    }

    private function sendMaterialLikedNotification(array $data): void
    {

        $payload = NotificationPayload::make(
            title: 'عملية تسجيل اعجاب',
            body: 'قام بتسجيل إعجابه بالمحتوى الخاص بك',
            metadata: [
                'type' => 'library_material_liked',
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
