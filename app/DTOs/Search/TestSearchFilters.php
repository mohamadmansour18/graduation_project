<?php

namespace App\DTOs\Search;

use App\Enums\TestSearchScope;
use App\Helpers\ArabicSearchNormalizer;

class TestSearchFilters
{
    public function __construct(
        public readonly string $query,
        public readonly int $userId,
        public readonly ?int $interestId = null,
        public readonly string $scope ,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}

    public static function fromRequest(array $data, int $userId , string $scope): self
    {
        return new self(
            query: ArabicSearchNormalizer::normalize($data['q']),
            userId: $userId,
            interestId: $data['interest_id'] ?? null,
            scope: $scope,
            page: (int) ($data['page'] ?? 1),
            perPage: 20,
        );
    }
}
