<?php

namespace App\Services\TestDiscovery\DTO;

final class TestCandidateData
{
    public function __construct(
        public readonly int $id,
        public readonly int $creatorUserId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?float $price,
        public readonly ?string $targetLevel,
        public readonly ?string $publishedAt,
        public readonly int $participantsCount,
        public readonly int $likesCount,
        public readonly float $averageRating,
        public readonly array $interestIds = [],
        public readonly array $matchedInterestIds = [],
        public readonly bool $matchedByTargetLevel = false,
        public readonly string $candidateBucket = 'fallback',
    )
    {}


    /**
     * هذا التابع يعيد عدد الاهتمامات المشتركة بين المستخدم والاختبار
     * مفيد جدًا في ranking policies
     */
    public function matchedInterestsCount(): int
    {
        return count($this->matchedInterestIds);
    }

    /**
     * هذا التابع يفحص هل الاختبار مجاني؟
     * سنحتاجه في السياسات أو التحقق.
     */
    public function isFree(): bool
    {
        return $this->price === null || (float) $this->price <= 0;
    }
}
