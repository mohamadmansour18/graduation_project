<?php

namespace App\Services\TestDiscovery\Policies;

use App\Services\TestDiscovery\DTO\TestCandidateData;
use App\Services\TestDiscovery\DTO\UserDiscoveryProfileData;
use App\Services\TestDiscovery\DTO\RankedCandidateData;

final class NewRankingPolicy extends BaseRankingPolicy
{
    /**
     * منطق "الجديد":
     * هنا الحداثة هي العامل الأقوى،
     * لكننا لا نهمل:
     * - الاهتمامات
     * - target level
     * - شعبية خفيفة جدًا
     *
     * الهدف:
     * الاختبار الجديد الجيد والمناسب يظهر أعلى،
     * بدون أن يقتله غياب المشاركات القديمة.
     */

    public function rank(TestCandidateData $candidate, UserDiscoveryProfileData $userProfile): RankedCandidateData
    {
        $interest = $this->interestScore($candidate, $userProfile, 7);
        $targetLevel = $this->targetLevelScore($candidate, $userProfile);
        $freshness = $this->freshnessScore($candidate) * 2.0;
        $participants = $this->participantsScore($candidate, 2.0);
        $rating = $this->ratingScore($candidate, 2.5);
        $bucket = $this->bucketScore($candidate);

        $score = $interest + $targetLevel + $freshness + $participants + $rating + $bucket;

        return $this->buildRankedCandidate($candidate, $score, [
            'interest_score' => $interest,
            'target_level_score' => $targetLevel,
            'freshness_score' => $freshness,
            'participants_score' => $participants,
            'rating_score' => $rating,
            'bucket_score' => $bucket,
        ]);
    }
}
