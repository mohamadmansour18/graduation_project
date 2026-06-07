<?php

namespace App\Services\LibraryMaterial;

use App\Events\LibraryMaterialLikeChanged;
use App\Exceptions\Api\LibraryMaterialException;
use App\Repositories\Library\LibraryMaterialLikeRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;

class LibraryMaterialLikeService
{
    public function __construct(
        private readonly LibraryMaterialLikeRepository $likeRepository)
    {}
    public function like(int $userId, int $materialId): void
    {
        $this->ensureMaterialInteractable($userId, $materialId);

        $created = $this->likeRepository->like($userId, $materialId);

        if ($created) {
            LibraryMaterialLikeChanged::dispatch($materialId , 1 , now() );
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
}
