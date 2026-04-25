<?php

namespace App\Services\TestDiscovery\Policies;

use App\Services\TestDiscovery\DTO\TestCandidateData;
use App\Services\TestDiscovery\DTO\UserDiscoveryProfileData;
use App\Services\TestDiscovery\DTO\RankedCandidateData;

final class FreeRankingPolicy extends BaseRankingPolicy
{
    /**
     * منطق "المجاني":
     * المرشحون هنا أصلًا وصلوا بعد filter مجاني،
     * لذلك لا نحتاج score خاص بالسعر نفسه.
     *
     * نريد مزيجًا من:
     * - الاهتمامات
     * - target level
     * - الحداثة
     * - شعبية خفيفة
     * - التقييم
     */
    public function rank(TestCandidateData $candidate, UserDiscoveryProfileData $userProfile): RankedCandidateData
    {
        $interest = $this->interestScore($candidate, $userProfile, 7);
        $targetLevel = $this->targetLevelScore($candidate, $userProfile);
        $freshness = $this->freshnessScore($candidate);
        $participants = $this->participantsScore($candidate, 4.0);
        $likes = $this->likesScore($candidate, 3.0);
        $rating = $this->ratingScore($candidate, 3.0);
        $bucket = $this->bucketScore($candidate);

        $score = $interest + $targetLevel + $freshness + $participants + $likes + $rating + $bucket;

        return $this->buildRankedCandidate($candidate, $score, [
            'interest_score' => $interest,
            'target_level_score' => $targetLevel,
            'freshness_score' => $freshness,
            'participants_score' => $participants,
            'likes_score' => $likes,
            'rating_score' => $rating,
            'bucket_score' => $bucket,
        ]);
    }
}
