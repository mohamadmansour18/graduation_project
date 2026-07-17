<?php

namespace App\Services\LibraryMaterial;

use App\DTOs\Notifications\NotificationPayload;
use App\Enums\Asset_type;
use App\Enums\LibraryMaterialContentKind;
use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\VisibilityType;
use App\Events\LibraryMaterialDeletedByOwner;
use App\Exceptions\Api\LibraryMaterialException;
use App\Helpers\BuildActor;
use App\Helpers\ImageProcessor;
use App\Http\Resources\LibraryMaterial\FeaturedLibraryMaterialResource;
use App\Http\Resources\LibraryMaterial\LibraryMaterialListResource;
use App\Models\LibraryMaterial;
use App\Repositories\Library\LibraryMaterialRepository;
use App\Services\AiQuestionGeneration\Validation\ImageContentHeuristicValidator;
use App\Services\AiQuestionGeneration\Validation\PdfStructureValidator;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class LibraryMaterialService
{
    private const int MAX_PENDING_PUBLIC_MATERIALS = 3;
    public function __construct(
        private readonly LibraryMaterialRepository $libraryMaterialRepository,
        private readonly NotificationCenter $notificationCenter,
    ) {}

    public function getLibraryMaterials(int $userId, string $tab = 'trending', int $perPage = 10, bool $includeFeatured = true): array
    {
        $featured = collect();

        if ($includeFeatured) {
            $featured = $this->libraryMaterialRepository->getFeaturedMaterials(
                userId: $userId,
                tab: $tab,
                limit: 6
            );
        }

        $featuredIds = $featured->pluck('id')->all();

        $materialsPaginator = $this->libraryMaterialRepository->cursorPaginateMaterials(
            userId: $userId,
            tab: $tab,
            perPage: $perPage,
            excludedIds: $featuredIds
        );


        return [
            'tab' => $tab,

            'featured' => FeaturedLibraryMaterialResource::collection($featured),

            'materials' => LibraryMaterialListResource::collection(
                $materialsPaginator->items()
            ),

            'meta' => [
                'per_page' => $materialsPaginator->perPage(),
                'next_cursor' => optional($materialsPaginator->nextCursor())->encode(),
                'previous_cursor' => optional($materialsPaginator->previousCursor())->encode(),
                'has_more_pages' => $materialsPaginator->hasMorePages(),
            ],
        ];
    }

    public function searchLibraryMaterials(int $userId, string $query, string $mode = 'all_public', int $perPage = 20): array
    {
        $materialsPaginator = $this->libraryMaterialRepository->searchMaterials(
            userId: $userId,
            query: $query,
            mode: $mode,
            perPage: $perPage
        );

        $materials = collect($materialsPaginator->items());

        if ($mode === 'user_owned') {
            $materials->each(function ($material) {
                $material->include_visibility_type = true;
            });
        }

        return [
            'query' => $query,
            'mode' => $mode,
            'materials' => LibraryMaterialListResource::collection($materials),
            'meta' => [
                'per_page' => $materialsPaginator->perPage(),
                'next_cursor' => optional($materialsPaginator->nextCursor())->encode(),
                'previous_cursor' => optional($materialsPaginator->previousCursor())->encode(),
                'has_more_pages' => $materialsPaginator->hasMorePages(),
            ],
        ];
    }

    ///////////////////////////////////////////////////////////////////////////////

    public function create(int $userId, array $data, array $files , PdfStructureValidator $pdfStructureValidator , ImageContentHeuristicValidator $imageContentHeuristicValidator): LibraryMaterial
    {
        $contentKind = $data['content_kind'];
        $visibilityType = $data['visibility_type'];

        if ($visibilityType === VisibilityType::Public->value) {
            $this->ensureUserCanCreatePublicMaterial($userId);
        }

        $this->processFilesBeforeStorage($contentKind , $files , $pdfStructureValidator , $imageContentHeuristicValidator);

        $storedPaths = [];

        try {
            $assetRows = $this->storeAssets(
                contentKind: $contentKind,
                files: $files,
                storedPaths: $storedPaths
            );

            $isPrivate = $visibilityType === VisibilityType::Private->value;

            $materialData = [
                'creator_user_id' => $userId,
                'title' => $data['title'],
                'description' => $data['description'],
                'content_kind' => $contentKind,
                'visibility_type' => $visibilityType,
                'target_level' => $data['target_level'],
                'review_status' => $isPrivate
                    ? LibraryMaterialReviewStatus::Approved->value
                    : LibraryMaterialReviewStatus::New->value,
                'current_approval_version' => $isPrivate ? 1 : 0,
                'published_at' => $isPrivate ? now() : null,
                'asset_count' => count($assetRows),
                'like_count' => 0,
                'bookmarks_count' => 0,
                'download_count' => 0,
            ];

            $material = $this->libraryMaterialRepository->createWithRelations(
                materialData: $materialData,
                assetRows: $assetRows,
                interestIds: $data['interest_ids'],
                shouldCreateReviewRound: ! $isPrivate,
                changedByUserId: $userId
            );

            if (! $isPrivate) {
                $this->sendNewLibraryMaterialRequiresReviewNotification([
                    'material_id' => (int) $material->id,
                    'material_title' => $material->title,
                    'creator_user_id' => $userId,
                    'content_kind' => $contentKind,
                    'visibility_type' => $visibilityType,
                    'asset_count' => count($assetRows),
                    'review_status' => LibraryMaterialReviewStatus::New->value,
                ]);
            }

            return $material;

        }catch (Throwable $exception)
        {
            $this->deleteStoredFiles($storedPaths);

            Log::channel('errors')->error('Failed to create library material.', [
                'user_id' => $userId,
                'content_kind' => $contentKind,
                'visibility_type' => $visibilityType,
                'exception_class' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function ensureUserCanCreatePublicMaterial(int $userId): void
    {
        $pendingCount = $this->libraryMaterialRepository->countPendingPublicMaterialsForUser($userId);

        if ($pendingCount > 3) {
            throw LibraryMaterialException::tooManyPendingPublicMaterials('! فشل إنشاء المحتوى');
        }
    }

    private function processFilesBeforeStorage(string $contentKind, array $files , PdfStructureValidator $pdfStructureValidator , ImageContentHeuristicValidator $imageContentHeuristicValidator): void
    {
        foreach (array_values($files) as $index => $file) {
            if ($contentKind === LibraryMaterialContentKind::File->value) {
                $pdfStructureValidator->validate($file);
                continue;
            }

            $imageContentHeuristicValidator->validate($file, $index);
        }
    }

    private function storeAssets(string $contentKind, array $files, array &$storedPaths): array
    {
        $assetRows = [];

        foreach (array_values($files) as $index => $file) {
            if ($contentKind === LibraryMaterialContentKind::File->value) {
                $path = ImageProcessor::uploadImage($file , 'library-material-file', 'public');
                $assetType = Asset_type::File->value;
            } else {
                $path = ImageProcessor::uploadImage($file, 'library-material-photo', 'public');
                $assetType = Asset_type::Image->value;
            }

            $storedPaths[] = [
                'disk' => 'public',
                'path' => $path,
            ];

            $assetRows[] = [
                'asset_type' => $assetType,
                'storage_disk' => 'public',
                'storage_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'position' => $index + 1,
            ];
        }

        return $assetRows;
    }

    private function deleteStoredFiles(array $storedPaths): void
    {
        foreach ($storedPaths as $storedFile) {
            Storage::disk($storedFile['disk'])->delete($storedFile['path']);
        }
    }

    public function getPublicMaterialDetails(int $viewerUserId, int $materialId): LibraryMaterial
    {
        $material = $this->libraryMaterialRepository->findPublicApprovedMaterialForOtherUser(
            viewerUserId: $viewerUserId,
            materialId: $materialId
        );

        if (! $material) {
            throw LibraryMaterialException::publicMaterialNotFound();
        }

        return $material;
    }

    public function getMyMaterialDetails(int $userId, int $materialId): LibraryMaterial
    {
        $material = $this->libraryMaterialRepository->findOwnedMaterialDetails(
            userId: $userId,
            materialId: $materialId
        );

        if (! $material) {
            throw LibraryMaterialException::ownedMaterialNotFound();
        }

        return $material;
    }

    ///////////////////////////////////////////////////////////////////////////////

    public function deleteOwnedMaterialPermanently(int $userId, int $materialId): void
    {
        $payload = DB::transaction(function () use ($userId, $materialId) {
            $material = $this->libraryMaterialRepository->findForOwnerDeleteWithLock($materialId);

            if (! $material) {
                throw LibraryMaterialException::unauthorizedAction();
            }

            if ((int) $material->creator_user_id !== $userId) {
                throw LibraryMaterialException::unauthorizedAction();
            }

            $files = $material->libraryMaterialAssets
                ->map(fn ($asset) => [
                    'disk' => $asset->storage_disk,
                    'path' => $asset->storage_path,
                ])
                ->filter(fn ($file) => filled($file['disk']) && filled($file['path']))
                ->values()
                ->all();

            $materialCreatedAt = $material->created_at;
            $materialLikesCount = (int) $material->like_count;
            $wasCountedAsPublished = $material->visibility_type === VisibilityType::Public->value;

            $this->libraryMaterialRepository->deleteUsingEloquent($material);

            return [
                'material_id' => $materialId,
                'deleted_by_user_id' => $userId,
                'material_created_at' => $materialCreatedAt,
                'material_likes_count' => $materialLikesCount,
                'files' => $files,
                'was_counted_as_published' => $wasCountedAsPublished,
            ];
        });


        DB::afterCommit(function () use ($payload) {
            $this->deleteMaterialFiles($payload['files']);

            if ($payload['was_counted_as_published']) {
                event(new LibraryMaterialDeletedByOwner(
                    materialId: $payload['material_id'],
                    deletedByUserId: $payload['deleted_by_user_id'],
                    materialCreatedAt: $payload['material_created_at'],
                    materialLikesCount: $payload['material_likes_count'],
                ));
            }
        });
    }

    private function deleteMaterialFiles(array $files): void
    {
        foreach ($files as $file) {
            try {
                Storage::disk($file['disk'])->delete($file['path']);
            } catch (Throwable $e) {
                Log::warning('Failed to delete library material file.', [
                    'disk' => $file['disk'],
                    'path' => $file['path'],
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////

    public function updateMine(int $userId, int $materialId, array $data): LibraryMaterial
    {
        $material = $this->libraryMaterialRepository->findOwnedMaterialForUpdate(
            userId: $userId,
            materialId: $materialId
        );

        if (! $material) {
            throw LibraryMaterialException::ownedMaterialNotFound();
        }

        if ($material->review_status === LibraryMaterialReviewStatus::Reported) {
            throw LibraryMaterialException::reportedMaterialCannotBeUpdated();
        }

        $requestedVisibility = $data['visibility_type'] ?? null;

        if (
            $requestedVisibility === VisibilityType::Private->value
            && $material->visibility_type === VisibilityType::Public
        ) {
            throw LibraryMaterialException::cannotConvertPublicMaterialToPrivate();
        }

        $shouldConvertPrivateToPublic =
            $requestedVisibility === VisibilityType::Public->value
            && $material->visibility_type === VisibilityType::Private;

        if ($shouldConvertPrivateToPublic) {
            $pendingPublicCount = $this->libraryMaterialRepository->countPendingPublicMaterialsForUser(
                userId: $userId
            );

            if ($pendingPublicCount >= self::MAX_PENDING_PUBLIC_MATERIALS) {
                throw LibraryMaterialException::tooManyPendingPublicMaterials('! فشل تعديل المحتوى');
            }
        }

        return $this->libraryMaterialRepository->updateOwnedMaterial(
            material: $material,
            data: $data,
            shouldConvertPrivateToPublic: $shouldConvertPrivateToPublic,
            changedByUserId: $userId
        );
    }

    ///////////////////////////////////////////////////////////////////////////////

    public function getSimilarMaterials(int $userId, array $interestIds, int $excludeMaterialId , int $perPage = 20): array
    {
        $paginator = $this->libraryMaterialRepository->cursorPaginateSimilarMaterials(
            userId: $userId,
            interestIds: $interestIds,
            excludeMaterialId: $excludeMaterialId,
            perPage: $perPage
        );

        return [
            'materials' => LibraryMaterialListResource::collection($paginator->items()),
            'meta' => [
                'per_page' => $paginator->perPage(),
                'next_cursor' => optional($paginator->nextCursor())->encode(),
                'previous_cursor' => optional($paginator->previousCursor())->encode(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ];
    }

    private function sendNewLibraryMaterialRequiresReviewNotification(array $data): void
    {
        $reviewerIds = $this->libraryMaterialRepository->getDashboardContentReviewerUserIds();

        if (empty($reviewerIds)) {
            return;
        }

        $materialTitle = $data['material_title'] ?? 'محتوى جديد';

        $payload = NotificationPayload::make(
            title: 'محتوى جديد بانتظار المراجعة',
            body: "قام مستخدم بإنشاء محتوى جديد بعنوان: {$materialTitle}",
            metadata: [
                'type' => 'library_material_created_requires_review',
                'category' => 'library_review',

                'presentation' => [
                    'mode' => 'user',
                    'floor_color' => null,
                    'icon' => null,
                ],

                'actor' => BuildActor::buildUserActor((int) $data['creator_user_id']),

                'navigation' => [
                    'screen' => 'material_details',
                    'action' => 'open',
                ],

                'params' => [
                    'material_id' => (int) $data['material_id'],
                ],

            ],
        );

        $this->notificationCenter->sendToWeb(
            userIds: $reviewerIds,
            payload: $payload,
        );
    }
}
