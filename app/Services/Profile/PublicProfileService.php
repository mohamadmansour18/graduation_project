<?php

namespace App\Services\Profile;

use App\Exceptions\Api\PublicProfileException;
use App\Models\User;
use App\Repositories\Profile\PublicProfileRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PublicProfileService
{
    public function __construct(
        private readonly PublicProfileRepository $repository
    ) {}

    public function getOverview(int $viewer, int $profileOwner): array
    {
        if ($viewer === $profileOwner) {
            throw PublicProfileException::cannotViewOwnPublicProfile();
        }

        $user = $this->repository->findOverviewUser(
            profileUserId: $profileOwner,
            viewerUserId: $viewer
        );

        $ratingCounts = $this->repository->getRatingCountsForUserTests($profileOwner);

        return [
            'user' => $user,
            'rating_counts' => $ratingCounts,
        ];
    }

    public function getUserPublishedTests(int $viewer, int $profileOwner, string $tab, int $perPage): CursorPaginator
    {
        if ($viewer === $profileOwner) {
            throw PublicProfileException::cannotViewOwnPublicProfile();
        }

        return $this->repository->cursorPaginatePublishedTestsForUser(
            profileUserId: $profileOwner,
            tab: $tab,
            perPage: $perPage
        );
    }

    public function getUserPublicFolders(int $viewer, int $profileOwner, int $perPage): CursorPaginator
    {
        if ($viewer === $profileOwner) {
            throw PublicProfileException::cannotViewOwnPublicProfile();
        }

        return $this->repository->cursorPaginatePublicFoldersForUser(
            profileUserId: $profileOwner,
            viewerUserId: $viewer,
            perPage: $perPage
        );
    }

    public function getUserPublicMaterials(int $viewer, int $profileOwner, string $tab, int $perPage): CursorPaginator
    {
        if ($viewer === $profileOwner) {
            throw PublicProfileException::cannotViewOwnPublicProfile();
        }

        return $this->repository->cursorPaginatePublicMaterialsForUser(
            profileUserId: $profileOwner,
            viewerUserId: $viewer,
            tab: $tab,
            perPage: $perPage
        );
    }

    public function getShareLink(int $profileOwner): array
    {
        $slug = DB::transaction(function () use ($profileOwner) {
            return $this->repository->getOrCreateProfileSlug($profileOwner);
        });

        return [
            'share_slug' => $slug,
            'share_url' => url('/share/profiles/' . $slug),
        ];
    }

    public function resolveSlug(string $slug, int $viewerId): array
    {
        $user = $this->repository->findUserByProfileSlug($slug);

        if (! $user) {
            throw PublicProfileException::profileShareLinkNotFound();
        }

        return [
            'user_id' => $user->id,
            'is_this_your_profile' => $viewerId === $user->id,
        ];
    }

    public function getFolderContent(int $viewerId, int $folderId): array
    {
        $folder = $this->repository->findPublicFolderForViewer(
            folderId: $folderId,
            viewerUserId: $viewerId
        );

        if (! $folder) {
            throw PublicProfileException::publicFolderNotFound();
        }

        $tests = $this->repository->FolderTests(
            folderId: $folder->id,
        );

        return [
            'folder' => $folder,
            'tests' => $tests,
        ];
    }

    public function getAcademicCertificate(int $profileOwner): \App\Models\UserAcademicAsset
    {
        $certificate = $this->repository->findApprovedCertificateForUser($profileOwner);

        if (! $certificate) {
            throw PublicProfileException::academicCertificateNotFound();
        }

        if (! Storage::disk($certificate->storage_disk)->exists($certificate->storage_path)) {
            throw PublicProfileException::academicCertificateNotFound();
        }

        return $certificate;
    }

    public function getFollowers(int $viewer, int $profileOwner, ?string $search, int $perPage): CursorPaginator
    {
        return $this->repository->cursorPaginateFollowers(
            profileUserId: $profileOwner,
            viewerUserId: $viewer,
            search: $search,
            perPage: $perPage
        );
    }

    public function getFollowing(int $viewer, int $profileOwner, ?string $search, int $perPage): CursorPaginator
    {
        return $this->repository->cursorPaginateFollowing(
            profileUserId: $profileOwner,
            viewerUserId: $viewer,
            search: $search,
            perPage: $perPage
        );
    }
}
