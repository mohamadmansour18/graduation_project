<?php

namespace App\Services\TestDiscovery\Policies;

use App\Services\TestDiscovery\DTO\TestCandidateData;
use App\Services\TestDiscovery\DTO\UserDiscoveryProfileData;
use App\Services\TestDiscovery\DTO\RankedCandidateData;

final class MostParticipatedRankingPolicy extends BaseRankingPolicy
{
    /**
     * منطق "الأكثر تقدمًا":
     * العامل الأقوى هنا هو participants_count،
     * لكننا لا نريد أن يصبح التاب عامًا بالكامل،
     * لذلك نبقي:
     * - الاهتمامات
     * - target level
     * - التقييم
     * - حداثة خفيفة
     */
    public function rank(TestCandidateData $candidate, UserDiscoveryProfileData $userProfile): RankedCandidateData
    {
        $interest = $this->interestScore($candidate, $userProfile, 6);
        $targetLevel = $this->targetLevelScore($candidate, $userProfile);
        $participants = $this->participantsScore($candidate, 14.0);
        $rating = $this->ratingScore($candidate, 4.0);
        $freshness = $this->freshnessScore($candidate) * 0.3;
        $bucket = $this->bucketScore($candidate);

        $score = $interest + $targetLevel + $participants + $rating + $freshness + $bucket;

        return $this->buildRankedCandidate($candidate, $score, [
            'interest_score' => $interest,
            'target_level_score' => $targetLevel,
            'participants_score' => $participants,
            'rating_score' => $rating,
            'freshness_score' => $freshness,
            'bucket_score' => $bucket,
        ]);
    }
}
