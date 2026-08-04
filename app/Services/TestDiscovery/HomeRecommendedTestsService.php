<?php

namespace App\Services\TestDiscovery;

use App\Repositories\TestDiscovery\RecommendedTestDetailsRepository;
use App\Services\TestDiscovery\DTO\DiscoveryContextData;
use App\Services\TestDiscovery\Enums\DiscoveryScreen;
use App\Services\TestDiscovery\Enums\DiscoveryTab;
class HomeRecommendedTestsService
{
    public function __construct(
        private readonly TestDiscoveryService $testDiscoveryService,
        private readonly RecommendedTestDetailsRepository $recommendedTestDetailsRepository,
    ) {
    }

    /**
     * هذا هو التابع الذي سيستعمله الـ controller.
     *
     * وظيفته:
     * 1) تحديد التاب المطلوب
     * 2) إنشاء DiscoveryContext خاص بشاشة Home
     * 3) استدعاء محرك التوصية
     * 4) جلب تفاصيل العرض للـ 10 اختبارات
     * 5) دمج score مع تفاصيل العرض في شكل نهائي مناسب للـ API
     */
    public function listForUser(int $userId, string $tab): array
    {
        $resolvedTab = $this->resolveTabEnum($tab);

        $context = new DiscoveryContextData(
            screen: DiscoveryScreen::HOME,
            tab: $resolvedTab,
            limit: 10,
            candidatePoolLimit: 60,
        );

        $rankedCandidates = $this->testDiscoveryService->discoverForUser(
            userId: $userId,
            context: $context,
        );

        if (empty($rankedCandidates)) {
            return [
                'current_tab' => $resolvedTab->value,
                'tests' => [],
            ];
        }

        $testIds = array_map(
            static fn ($rankedCandidate): int => $rankedCandidate->candidate->id,
            $rankedCandidates
        );

        $detailsByTestId = $this->recommendedTestDetailsRepository->findDisplayDataByTestIds($testIds);

        $tests = [];

        /**
         * مهم جدًا:
         * نلف على rankedCandidates نفسها حتى نحافظ على الترتيب النهائي
         * الذي خرج من محرك التوصية.
         */
        foreach ($rankedCandidates as $rankedCandidate) {
            $testId = $rankedCandidate->candidate->id;

            if (! isset($detailsByTestId[$testId])) {
                continue;
            }

            $details = $detailsByTestId[$testId];

            $tests[] = [
                ...$details,

                'recommendation' => [
                    'score' => $rankedCandidate->score,
                    'score_breakdown' => $rankedCandidate->scoreBreakdown,
                    'candidate_bucket' => $rankedCandidate->candidate->candidateBucket,
                    'matched_interest_ids' => $rankedCandidate->candidate->matchedInterestIds,
                    'matched_interests_count' => $rankedCandidate->candidate->matchedInterestsCount(),
                    'matched_by_target_level' => $rankedCandidate->candidate->matchedByTargetLevel,
                ],
            ];
        }

        return [
            'current_tab' => $resolvedTab->value,
            'tests' => $tests,
        ];
    }

    private function resolveTabEnum(string $tab): DiscoveryTab
    {
        return match ($tab) {
            'new' => DiscoveryTab::NEW,
            'free' => DiscoveryTab::FREE,
            'most_participated' => DiscoveryTab::MOST_PARTICIPATED,
            default => DiscoveryTab::TRENDING,
        };
    }
}
