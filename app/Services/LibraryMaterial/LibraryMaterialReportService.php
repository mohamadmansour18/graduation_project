<?php

namespace App\Services\LibraryMaterial;

use App\Repositories\Library\LibraryMaterialReportRepository;
use App\Support\LibraryMaterialReportThresholdPolicy;

class LibraryMaterialReportService
{
    public function __construct(
        private readonly LibraryMaterialReportRepository $repository,
        private readonly LibraryMaterialReportThresholdPolicy $thresholdPolicy
    ) {}

    public function report(int $userId, int $materialId, string $reason, ?string $description): array
    {
        return $this->repository->createReportAndMaybeMarkAsReported(
            userId: $userId,
            materialId: $materialId,
            reason: $reason,
            description: $description,
            thresholdPolicy: $this->thresholdPolicy
        );
    }
}
