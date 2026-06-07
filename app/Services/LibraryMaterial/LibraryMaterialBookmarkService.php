<?php

namespace App\Services\LibraryMaterial;

use App\Exceptions\Api\LibraryMaterialException;
use App\Repositories\Library\LibraryMaterialBookmarkRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;


class LibraryMaterialBookmarkService
{
    public function __construct(
        private readonly LibraryMaterialBookmarkRepository $bookmarkRepository)
    {}
    public function bookmark(int $userId, int $materialId): void
    {
        $this->ensureMaterialInteractable($userId, $materialId);

        $this->bookmarkRepository->bookmark($userId, $materialId);
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
}
