<?php

namespace App\Services\TestDiscovery\Policies;

use App\Services\TestDiscovery\Contracts\RankingPolicy;
use App\Services\TestDiscovery\DTO\TestCandidateData;
use App\Services\TestDiscovery\DTO\UserDiscoveryProfileData;
use App\Services\TestDiscovery\DTO\RankedCandidateData;
use Carbon\CarbonImmutable;

/**
 * هذا الكلاس الأساسي لا يُستخدم مباشرة.
 *
 * وظيفته:
 * - تجميع الدوال المشتركة بين جميع الـ policies
 * - حتى لا نكرر نفس المنطق في 4 ملفات مختلفة
 */
abstract class BaseRankingPolicy implements RankingPolicy
{
    abstract public function rank(TestCandidateData $candidate, UserDiscoveryProfileData $userProfile): RankedCandidateData;

    /**
     * حساب نقاط الاهتمامات:
     * نجمع أوزان الاهتمامات المشتركة بين المستخدم والاختبار.
     *
     * مثال:
     * إذا وافق الاختبار اهتمامين للمستخدم،
     * الأول وزنه 5 والثاني وزنه 3،
     * تصبح النقاط الخام = 8
     */
    protected function rawInterestWeightScore(TestCandidateData $candidate, UserDiscoveryProfileData $userProfile): int
    {
        $score = 0;

        foreach ($candidate->matchedInterestIds as $interestId) {
            $score += $userProfile->weightForInterest((int) $interestId);
        }

        return $score;
    }

    /**
     * تحويل نقاط الاهتمامات الخام إلى score فعلي قابل للمقارنة.
     *
     * استخدمنا معاملًا بسيطًا:
     * كل نقطة وزن خام تضرب في multiplier.
     */
    protected function interestScore(TestCandidateData $candidate, UserDiscoveryProfileData $userProfile, int $multiplier = 8): float
    {
        return $this->rawInterestWeightScore($candidate, $userProfile) * $multiplier;
    }

    /**
     * نقاط target level:
     * - primary   = أقوى bonus
     * - secondary = bonus متوسط
     * - broad     = bonus خفيف
     *
     * هذا يحقق هدفنا السابق:
     * target level في هذه المرحلة Bonus وليس Hard Filter.
     */
    protected function targetLevelScore(TestCandidateData $candidate, UserDiscoveryProfileData $userProfile): float
    {
        $targetLevel = $candidate->targetLevel;

        if ($targetLevel === null || $targetLevel === '') {
            return 0.0;
        }

        $preference = $userProfile->targetLevelPreference;

        if ($preference->isPrimary($targetLevel)) {
            return 30.0;
        }

        if ($preference->isSecondary($targetLevel)) {
            return 15.0;
        }

        if ($preference->isBroad($targetLevel)) {
            return 5.0;
        }

        return 0.0;
    }

    /**
     * نقاط الحداثة:
     * كلما كان الاختبار أحدث زادت نقاطه.
     *
     * نحن لا نحتاج هنا formula معقدة.
     * استخدمنا شرائح زمنية سهلة الفهم:
     * - <= 7 أيام   => 25
     * - <= 30 يومًا => 18
     * - <= 90 يومًا => 10
     * - <= 180 يومًا => 5
     * - أقدم من ذلك => 0
     */
    protected function freshnessScore(TestCandidateData $candidate): float
    {
        if ($candidate->publishedAt === null || trim($candidate->publishedAt) === '') {
            return 0.0;
        }

        try {
            $publishedAt = CarbonImmutable::parse($candidate->publishedAt);
        } catch (\Throwable) {
            return 0.0;
        }

        $days = $publishedAt->diffInDays(now());

        return match (true) {
            $days <= 7 => 25.0,
            $days <= 30 => 18.0,
            $days <= 90 => 10.0,
            $days <= 180 => 5.0,
            default => 0.0,
        };
    }

    /**
     * نقاط المشاركات:
     * استخدمنا log حتى لا يهيمن اختبار ضخم جدًا على كل شيء.
     *
     * log(1 + participants_count) يعطي نموًا أبطأ وأكثر عدلًا
     */
    protected function participantsScore(TestCandidateData $candidate, float $multiplier = 10.0): float
    {
        return log(1 + max(0, $candidate->participantsCount), 2) * $multiplier;
    }

    /**
     * نقاط الإعجابات:
     * أيضًا باستخدام log لنفس السبب.
     */
    protected function likesScore(TestCandidateData $candidate, float $multiplier = 6.0): float
    {
        return log(1 + max(0, $candidate->likesCount), 2) * $multiplier;
    }

    /**
     * نقاط التقييم:
     * نفترض أن average_rating من 0 إلى 5
     * نحوله إلى score بسيط.
     */
    protected function ratingScore(TestCandidateData $candidate, float $multiplier = 4.0): float
    {
        $rating = max(0.0, min(5.0, $candidate->averageRating));

        return $rating * $multiplier;
    }

    /**
     * bonus خفيف حسب bucket الذي جاء منه الـ candidate.
     *
     * الفكرة:
     * إذا دخل من bucket الاهتمامات، نعطيه أولوية خفيفة.
     * إذا دخل من target_level، bonus أخف.
     * fallback لا يأخذ شيئًا.
     */

    protected function bucketScore(TestCandidateData $candidate): float
    {
        return match ($candidate->candidateBucket) {
            'interest_match' => 12.0,
            'target_level_match' => 6.0,
            default => 0.0,
        };
    }


    protected function buildRankedCandidate(TestCandidateData $candidate, float $score, array $breakdown): RankedCandidateData {
        return new RankedCandidateData(
            candidate: $candidate,
            score: round($score, 2),
            scoreBreakdown: $breakdown,
        );
    }
}
