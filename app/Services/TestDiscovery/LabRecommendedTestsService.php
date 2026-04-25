<?php

namespace App\Services\TestDiscovery;

use App\Repositories\TestDiscovery\RecommendedTestDetailsRepository;
use App\Services\TestDiscovery\DTO\DiscoveryContextData;
use App\Services\TestDiscovery\DTO\RankedCandidateData;
use App\Services\TestDiscovery\Enums\DiscoveryScreen;
use App\Services\TestDiscovery\Enums\DiscoveryTab;

final class LabRecommendedTestsService
{
    /**
     * هنا ثوابت الشاشة:
     * - 4 featured cards في الأعلى
     * - 20 عنصرًا في القائمة لكل صفحة
     *
     * يمكن تعديلها لاحقًا من مكان واحد بسهولة.
     */
    private const FEATURED_COUNT = 4;
    private const LIST_PER_PAGE = 20;

    /**
     * هذا الـ service ينسق منطق شاشة المختبر.
     *
     * ما الذي يفعله؟
     * 1) يجلب ranking window من محرك التوصية
     * 2) يختار أول 4 اختبارات مميزة "الأعلى تقييمًا"
     * 3) يحذفها من القائمة العادية
     * 4) يطبق pagination على بقية القائمة
     * 5) يجلب تفاصيل العرض النهائية
     * 6) يدمج نتيجة التوصية مع تفاصيل العرض حتى نراها في الـ API
     */
    public function __construct(
        private readonly TestDiscoveryService $testDiscoveryService,
        private readonly RecommendedTestDetailsRepository $recommendedTestDetailsRepository,
    ) {
    }

    public function listForUser(int $userId, string $tab, int $page = 1): array
    {
        $resolvedTab = $this->resolveTabEnum($tab);
        $page = max(1, $page);

        /**
         * أولًا:
         * نبني نافذة ثابتة نسبيًا لاختيار featured items بشكل مستقر.
         *
         * لماذا؟
         * حتى لا تتغير featured cards بشكل مزعج بين صفحة 1 وصفحة 2.
         */
        $featuredSelectionContext = new DiscoveryContextData(
            screen: DiscoveryScreen::LAB,
            tab: $resolvedTab,
            limit: 100,
            candidatePoolLimit: 220,
        );

        $featuredSelectionRanked = $this->testDiscoveryService->discoverForUser(
            userId: $userId,
            context: $featuredSelectionContext,
        );

        $featuredRanked = $this->pickTopRatedFeaturedItems($featuredSelectionRanked);

        $featuredIds = array_map(
            static fn (RankedCandidateData $item): int => $item->candidate->id,
            $featuredRanked
        );

        /**
         * ثانيًا:
         * نبني نافذة كافية للقائمة بحسب الصفحة الحالية.
         *
         * مثال:
         * page 1 => نحتاج تقريبًا 24 + هامش
         * page 2 => نحتاج تقريبًا 44 + هامش
         */
        $neededListItemsUntilCurrentPage = $page * self::LIST_PER_PAGE;
        $rankingWindowLimit = self::FEATURED_COUNT + $neededListItemsUntilCurrentPage + self::LIST_PER_PAGE;

        $listingContext = new DiscoveryContextData(
            screen: DiscoveryScreen::LAB,
            tab: $resolvedTab,
            limit: max(40, $rankingWindowLimit),
            candidatePoolLimit: max(180, $rankingWindowLimit * 4),
        );

        $listingRanked = $this->testDiscoveryService->discoverForUser(
            userId: $userId,
            context: $listingContext,
        );

        /**
         * نحذف featured items من القائمة العادية
         * حتى لا تتكرر للمستخدم.
         */
        $filteredRankedList = array_values(array_filter(
            $listingRanked,
            static fn (RankedCandidateData $item): bool => ! in_array($item->candidate->id, $featuredIds, true)
        ));

        $offset = ($page - 1) * self::LIST_PER_PAGE;
        $listPageRanked = array_slice($filteredRankedList, $offset, self::LIST_PER_PAGE);

        /**
         * has_more هنا مبني على ranking window الحالي.
         * هذا ممتاز كنسخة أولى.
         * لاحقًا سنقويه عندما نصل إلى precomputed ranks / stronger pagination.
         */
        $hasMore = count($filteredRankedList) > ($offset + self::LIST_PER_PAGE);

        $allIdsNeeded = array_values(array_unique(array_merge(
            $featuredIds,
            array_map(
                static fn (RankedCandidateData $item): int => $item->candidate->id,
                $listPageRanked
            ),
        )));

        $detailsByTestId = $this->recommendedTestDetailsRepository->findDisplayDataByTestIds($allIdsNeeded);

        $featured = [];
        foreach ($featuredRanked as $rankedItem) {
            $testId = $rankedItem->candidate->id;

            if (! isset($detailsByTestId[$testId])) {
                continue;
            }

            /**
             * هنا ندمج:
             * - تفاصيل العرض
             * - مع recommendation score و breakdown
             */
            $featured[] = [
                ...$detailsByTestId[$testId],
                'recommendation' => [
                    'score' => $rankedItem->score,
                    'score_breakdown' => $rankedItem->scoreBreakdown,
                    'candidate_bucket' => $rankedItem->candidate->candidateBucket,
                    'matched_interest_ids' => $rankedItem->candidate->matchedInterestIds,
                    'matched_interests_count' => $rankedItem->candidate->matchedInterestsCount(),
                    'matched_by_target_level' => $rankedItem->candidate->matchedByTargetLevel,
                ],
            ];
        }

        $list = [];
        foreach ($listPageRanked as $rankedItem) {
            $testId = $rankedItem->candidate->id;

            if (! isset($detailsByTestId[$testId])) {
                continue;
            }

            /**
             * نفس فكرة featured cards:
             * نضيف نتيجة التوصية أيضًا لعناصر القائمة،
             * حتى نستطيع تحليل عمل النظام في المختبر كذلك.
             */
            $list[] = [
                ...$detailsByTestId[$testId],
                'recommendation' => [
                    'score' => $rankedItem->score,
                    'score_breakdown' => $rankedItem->scoreBreakdown,
                    'candidate_bucket' => $rankedItem->candidate->candidateBucket,
                    'matched_interest_ids' => $rankedItem->candidate->matchedInterestIds,
                    'matched_interests_count' => $rankedItem->candidate->matchedInterestsCount(),
                    'matched_by_target_level' => $rankedItem->candidate->matchedByTargetLevel,
                ],
            ];
        }

        /**
         * featured تعرض فقط في الصفحة الأولى.
         * هذا قرار UX وهندسة معًا:
         * - الصفحة الأولى تبدو غنية
         * - الصفحات التالية لا تكرر نفس البطاقات المميزة
         */
        return [
            'current_tab' => $resolvedTab->value,
            'featured_top_rated' => $page === 1 ? $featured : [],
            'list' => $list,
            'pagination' => [
                'current_page' => $page,
                'per_page' => self::LIST_PER_PAGE,
                'has_more' => $hasMore,
            ],
        ];
    }

    /**
     * اختيار أول 4 اختبارات "الأعلى تقييمًا" من داخل النافذة الموصى بها.
     *
     * لاحظ:
     * لا نأخذ أول 4 من ranking مباشرة،
     * بل نعيد فرز النافذة على أساس rating أولًا،
     * لأن هذا هو المطلوب في شاشة المختبر.
     */
    private function pickTopRatedFeaturedItems(array $rankedCandidates): array
    {
        if (empty($rankedCandidates)) {
            return [];
        }

        usort($rankedCandidates, static function (RankedCandidateData $a, RankedCandidateData $b): int {
            $ratingCompare = $b->candidate->averageRating <=> $a->candidate->averageRating;
            if ($ratingCompare !== 0) {
                return $ratingCompare;
            }

            $scoreCompare = $b->score <=> $a->score;
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return $b->candidate->participantsCount <=> $a->candidate->participantsCount;
        });

        return array_slice($rankedCandidates, 0, self::FEATURED_COUNT);
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
