<?php

namespace App\Services\TestDiscovery;

use App\Services\TestDiscovery\DTO\DiscoveryContextData;
use App\Services\TestDiscovery\DTO\RankedCandidateData;
final class TestDiscoveryService
{
    /**
     * هذا هو السيرفس الرئيسي الذي يربط المراحل التي بنيناها معًا:
     *
     * 1) بناء صورة المستخدم
     * 2) اختيار المرشحين
     * 3) اختيار ranking policy المناسبة
     * 4) حساب score لكل مرشح
     * 5) ترتيب النتائج
     * 6) قصّها حسب limit النهائي
     *
     * هذا هو "قلب" نظام التوصية في نسخته الحالية.
     */

    public function __construct(
        private readonly UserDiscoveryProfileService $userDiscoveryProfileService,
        private readonly TestCandidateSelectionService $testCandidateSelectionService,
        private readonly RankingPolicyResolver $rankingPolicyResolver,
    ) {}

    public function discoverForUser(int $userId, DiscoveryContextData $context): array
    {
        // أولًا: نبني صورة المستخدم الخاصة بالتوصية.
        $userProfile = $this->userDiscoveryProfileService->buildForUser($userId);

        // ثانيًا: نختار candidate pool أولي.
        $candidates = $this->testCandidateSelectionService->selectCandidates($userProfile, $context);

        if (empty($candidates)) {
            return [];
        }

        // ثالثًا: نحدد policy الترتيب المناسبة حسب التاب.
        $policy = $this->rankingPolicyResolver->resolve($context->tab);

        // رابعًا: نحسب score لكل اختبار مرشح.
        $rankedCandidates = [];
        foreach ($candidates as $candidate) {
            $rankedCandidates[] = $policy->rank($candidate, $userProfile);
        }

        // خامسًا: نرتب من الأعلى score إلى الأقل
        // إذا تساوت الدرجات:
        // - نكسر التعادل بالأحدث publishedAt
        // - ثم بالأكبر id
        usort($rankedCandidates, function (RankedCandidateData $a, RankedCandidateData $b): int {
            if ($a->score !== $b->score) {
                return $b->score <=> $a->score;
            }

            $aPublishedAt = $a->candidate->publishedAt ?? '';
            $bPublishedAt = $b->candidate->publishedAt ?? '';

            if ($aPublishedAt !== $bPublishedAt) {
                return strcmp($bPublishedAt, $aPublishedAt);
            }

            return $b->candidate->id <=> $a->candidate->id;
        });

        // أخيرًا: نقص النتائج بحسب limit المطلوب للواجهة.
        return array_slice($rankedCandidates, 0, $context->limit);
    }
}
