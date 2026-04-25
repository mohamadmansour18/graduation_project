<?php

namespace App\Services\TestDiscovery\DTO;

final class UserDiscoveryRawData
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $educationLevel,
        public readonly ?string $schoolStage,
        public readonly ?string $universityName,
        public readonly ?string $universityDepartment,
        public readonly ?int $universityYear,
        public readonly array $interestSelections = [],
    ) {
    }

    /**
     * هذا التابع يعطينا فقط interest_ids بدون slot_no
     * وهو مفيد لاحقًا عندما نحتاج فلترة سريعة أو مقارنة مباشرة
     */
    public function interestIds(): array
    {
        $interestIds = array_map(
            static fn (array $row): int => (int) $row['interest_id'],
            $this->interestSelections
        );

        return array_values(array_unique($interestIds));
    }
}
