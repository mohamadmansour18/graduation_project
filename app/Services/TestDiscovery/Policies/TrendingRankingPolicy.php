<?php

namespace App\Services\TestDiscovery\Policies;

use App\Services\TestDiscovery\DTO\RankedCandidateData;
use App\Services\TestDiscovery\DTO\TestCandidateData;
use App\Services\TestDiscovery\DTO\UserDiscoveryProfileData;

final class TrendingRankingPolicy extends BaseRankingPolicy
{
    /**
     * منطق "الرائج":
     * نريد خليطًا من:
     * - الاهتمامات
     * - target level
     * - المشاركات
     * - الإعجابات
     * - التقييم
     * - حداثة خفيفة
     *
     * الرائج ليس هو "الأكثر مشاركة فقط"،
     * بل اختبار حي، جيد، ومناسب للمستخدم.
     */
    public function rank(TestCandidateData $candidate, UserDiscoveryProfileData $userProfile): RankedCandidateData
    {
        $interest = $this->interestScore($candidate, $userProfile, 8);
        $targetLevel = $this->targetLevelScore($candidate, $userProfile);
        $participants = $this->participantsScore($candidate, 10.0);
        $likes = $this->likesScore($candidate, 6.0);
        $rating = $this->ratingScore($candidate, 4.0);
        $freshness = $this->freshnessScore($candidate) * 0.5;
        $bucket = $this->bucketScore($candidate);

        $score = $interest + $targetLevel + $participants + $likes + $rating + $freshness + $bucket;

        return $this->buildRankedCandidate($candidate, $score, [
            'interest_score' => $interest,
            'target_level_score' => $targetLevel,
            'participants_score' => $participants,
            'likes_score' => $likes,
            'rating_score' => $rating,
            'freshness_score' => $freshness,
            'bucket_score' => $bucket,
        ]);
    }
}
