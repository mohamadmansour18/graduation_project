<?php

namespace App\Services\TestDiscovery\Contracts;

use App\Services\TestDiscovery\DTO\RankedCandidateData;
use App\Services\TestDiscovery\DTO\TestCandidateData;
use App\Services\TestDiscovery\DTO\UserDiscoveryProfileData;

/**
 * هذا الـ contract يفرض شكلًا موحدًا لكل Ranking Policy.
 *
 * الفكرة:
 * كل تاب سيملك policy مختلفة،
 * لكن الجميع يجب أن يلتزم بنفس التوقيع.
 */
interface RankingPolicy
{
    /**
     * هذا التابع يحسب النتيجة النهائية لاختبار مرشح واحد،
     * ثم يرجع كائنًا يحتوي:
     * - الاختبار نفسه
     * - الدرجة النهائية
     * - breakdown يشرح من أين جاءت الدرجة
     */
    public function rank(
        TestCandidateData $candidate,
        UserDiscoveryProfileData $userProfile
    ): RankedCandidateData;
}
