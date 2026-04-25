<?php

namespace App\Services\TestDiscovery\DTO;


final class UserDiscoveryProfileData
{

    public function __construct(
        public readonly int $userId,
        public readonly ?string $educationLevel,
        public readonly ?string $schoolStage,
        public readonly ?string $universityName,
        public readonly ?string $universityDepartment,
        public readonly ?int $universityYear,
        public readonly array $interestIds,
        public readonly array $weightedInterests,
        public readonly TargetLevelPreferenceData $targetLevelPreference,
    ) {
    }

    /**
     * هذا التابع يعيد كل target levels المناسبة للمستخدم
     * (primary + secondary + broad) في قائمة واحدة.
     */
    public function allPreferredTargetLevels(): array
    {
        return $this->targetLevelPreference->allLevels();
    }


    /**
     * هذا التابع يعيد وزن اهتمام معين إن وجد،
     * وإلا يعيد صفر
     * سنحتاجه لاحقًا داخل ranking policies
     */
    public function weightForInterest(int $interestId): int
    {
        return $this->weightedInterests[$interestId] ?? 0;
    }
}
