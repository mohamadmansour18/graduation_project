<?php

namespace App\Services\TestDiscovery\DTO;

final class RankedCandidateData
{
    /**
     * هذا DTO يمثل "اختبارًا بعد حساب الدرجة".
     *
     * لاحقًا يمكن استعماله في:
     * - debug
     * - logs
     * - عرض breakdown داخلي
     * - المقارنة بين نتائج السياسات
     */
    public function __construct(
        public readonly TestCandidateData $candidate,
        public readonly float $score,
        public readonly array $scoreBreakdown = [],
    ) {
    }
}
