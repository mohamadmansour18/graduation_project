<?php

namespace App\Services\Admin;

use App\Http\Resources\LibraryMaterialListResource;
use App\Models\LibraryMaterial;
use App\Repositories\Admin\LibraryDashboardRepository;
use App\Repositories\Library\LibraryMaterialRepository;

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

}
