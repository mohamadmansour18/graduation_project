<?php

namespace App\Services\Admin;

use App\Enums\LibraryMaterialReviewStatus;
use App\Events\LibraryMaterialFirstApproved;
use App\Events\LibraryMaterialPublishedDeleted;
use App\Exceptions\Api\LibraryMaterialException;
use App\Http\Resources\LibraryMaterialListResource;
use App\Models\LibraryMaterial;
use App\Models\User;
use App\Repositories\Admin\LibraryDashboardRepository;
use App\Repositories\Library\LibraryMaterialRepository;
use Illuminate\Support\Facades\DB;
use Log;

class LibraryDashboardService
{
    public function __construct(
        private readonly LibraryDashboardRepository $repository,
        private readonly LibraryMaterialRepository $materialRepository,
    )
    {}

    /////////////////////////////////////////////////////////////
    public function listApprovedMaterials(array $filters): array
    {
        $perPage = min((int) ($filters['per_page'] ?? 20), 50);
        $sortBy = $filters['sort_by'] ?? 'latest';

        $materialsPaginator = $this->repository->paginateApprovedMaterials(
            sortBy: $sortBy,
            perPage: $perPage
        );

        return [
            'statistics' => $this->repository->getCurrentYearStatistics(),
            'materials' => LibraryMaterialListResource::collection($materialsPaginator->items()),
            'meta' => [
                'per_page' => $materialsPaginator->perPage(),
                'next_cursor' => optional($materialsPaginator->nextCursor())->encode(),
                'previous_cursor' => optional($materialsPaginator->previousCursor())->encode(),
                'has_more_pages' => $materialsPaginator->hasMorePages(),
            ],
        ];
    }

    /////////////////////////////////////////////////////////////
    public function searchLibraryMaterials(int $userId, string $query, string $mode = 'all_public', int $perPage = 20): array
    {
        $materialsPaginator = $this->materialRepository->searchMaterials(
            userId: $userId,
            query: $query,
            mode: $mode,
            perPage: $perPage
        );

        return [
            'query' => $query,
            'mode' => $mode,
            'materials' => LibraryMaterialListResource::collection($materialsPaginator),
            'meta' => [
                'per_page' => $materialsPaginator->perPage(),
                'next_cursor' => optional($materialsPaginator->nextCursor())->encode(),
                'previous_cursor' => optional($materialsPaginator->previousCursor())->encode(),
                'has_more_pages' => $materialsPaginator->hasMorePages(),
            ],
        ];
    }

    /////////////////////////////////////////////////////////////

    public function showMaterialDetails(int $libraryMaterialId): LibraryMaterial
    {
        return $this->repository->findMaterialDetailsOrFail($libraryMaterialId);
    }

    /////////////////////////////////////////////////////////////
    public function getCurrentVersionReports(int $libraryMaterialId, array $filters): array
    {
        $material = $this->repository->findMaterialForReportsOrFail($libraryMaterialId);

        $approvalVersion = (int) $material->current_approval_version;
        $perPage = min((int) ($filters['per_page'] ?? 20), 50);

        $reportsPaginator = $this->repository->paginateReportsForApprovalVersion(
            libraryMaterialId: $material->id,
            approvalVersion: $approvalVersion,
            perPage: $perPage
        );

        return [
            'material' => $material,
            'approval_version' => $approvalVersion,
            'reason_counters' => $this->repository->getReasonCountersForApprovalVersion(
                libraryMaterialId: $material->id,
                approvalVersion: $approvalVersion
            ),
            'reports_paginator' => $reportsPaginator,
        ];
    }

    /////////////////////////////////////////////////////////////

    public function getStatusHistory(int $libraryMaterialId): array
    {
        $material = $this->repository->findMaterialOrFail($libraryMaterialId);

        return [
            'material' => $material,
            'histories' => $this->repository->getHistories($libraryMaterialId),
        ];
    }

    /////////////////////////////////////////////////////////////

    public function approve(User $user, int $libraryMaterialId): void
    {
        $eventPayload = null;

        DB::transaction(function () use ($user, $libraryMaterialId, &$eventPayload)
        {
            $material = $this->repository->findMaterialForUpdateOrFail($libraryMaterialId);

            $fromStatus = $material->review_status->value;

            if ($fromStatus === LibraryMaterialReviewStatus::Approved->value) {
                throw LibraryMaterialException::alreadyApproved();
            }

            if ($fromStatus === LibraryMaterialReviewStatus::Deleted->value) {
                throw LibraryMaterialException::deletedMaterialCannotBeApproved();
            }

            if (! in_array($fromStatus, [LibraryMaterialReviewStatus::New->value , LibraryMaterialReviewStatus::Reported->value], true)) {
                throw LibraryMaterialException::invalidStatusForApproval();
            }

            $openRound = $this->repository->findOpenReviewRoundForUpdate($material->id);

            if (! $openRound) {
                throw LibraryMaterialException::noOpenReviewRound();
            }

            $now = now();

            $isFirstApproval = $fromStatus === LibraryMaterialReviewStatus::New->value;

            $nextApprovalVersion = $isFirstApproval
                ? 1
                : (int) $material->current_approval_version;

            $publishedAt = $isFirstApproval
                ? $now
                : $material->published_at;

            $this->repository->approveOpenRound(
                round: $openRound,
                reviewerUserId: $user->id,
                decidedAt: $now
            );

            $this->repository->approveMaterial(
                material: $material,
                approvalVersion: $nextApprovalVersion,
                publishedAt: $publishedAt
            );

            $this->repository->createStatusHistory(
                libraryMaterialId: $material->id,
                fromStatus: $fromStatus,
                toStatus: LibraryMaterialReviewStatus::Approved->value,
                changedByUserId: $user->id,
                note: $isFirstApproval
                    ? 'تمت الموافقة على نشر المحتوى لأول مرة'
                    : 'تمت الموافقة على المحتوى بعد مراجعة البلاغات'
            );

            Log::channel('audit')->info('library_material_approved', [
                'library_material_id' => $material->id,
                'approved_by_user_id' => $user->id,
                'from_status' => $fromStatus,
                'to_status' => LibraryMaterialReviewStatus::Approved->value,
                'approval_version' => $nextApprovalVersion,
                'is_first_approval' => $isFirstApproval,
            ]);

            if ($isFirstApproval) {
                $eventPayload = [
                    'library_material_id' => $material->id,
                    'published_at' => $publishedAt,
                ];
            }
        });

        if ($eventPayload !== null) {
            LibraryMaterialFirstApproved::dispatch(
                $eventPayload['library_material_id'],
                $eventPayload['published_at']
            );
        }
    }

    /////////////////////////////////////////////////////////////

    public function delete(User $user, int $libraryMaterialId , string $deleteReason): void
    {
        $eventPayload = null;

        DB::transaction(function () use ($user, $libraryMaterialId, &$eventPayload) {
            $material = $this->repository->findMaterialForUpdateOrFail($libraryMaterialId);

            $fromStatus = $material->review_status->value;

            if ($fromStatus === LibraryMaterialReviewStatus::Deleted->value) {
                throw LibraryMaterialException::alreadyDeleted();
            }

            $shouldDecrementSummary = in_array($fromStatus ,[LibraryMaterialReviewStatus::Approved->value , LibraryMaterialReviewStatus::Reported->value] , true)
                && $material->published_at !== null;

            if ($shouldDecrementSummary) {
                $eventPayload = [
                    'library_material_id' => $material->id,
                    'published_at' => $material->published_at,
                ];
            }

            Log::channel('audit')->info('library_material_force_deleted', [
                'library_material_id' => $material->id,
                'deleted_by_user_id' => $user->id,
                'from_status' => $fromStatus,
                'was_published' => $shouldDecrementSummary,
                'published_at' => optional($material->published_at)->toDateTimeString(),
            ]);

            $this->repository->forceDeleteMaterial($material);
        });

        if ($eventPayload !== null) {
            LibraryMaterialPublishedDeleted::dispatch(
                $eventPayload['library_material_id'],
                $eventPayload['published_at']
            );
        }
    }

}
