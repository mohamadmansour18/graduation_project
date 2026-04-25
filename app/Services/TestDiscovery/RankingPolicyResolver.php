<?php

namespace App\Services\TestDiscovery;

use App\Services\TestDiscovery\Contracts\RankingPolicy;
use App\Services\TestDiscovery\Enums\DiscoveryTab;
use App\Services\TestDiscovery\Policies\FreeRankingPolicy;
use App\Services\TestDiscovery\Policies\MostParticipatedRankingPolicy;
use App\Services\TestDiscovery\Policies\NewRankingPolicy;
use App\Services\TestDiscovery\Policies\TrendingRankingPolicy;

final class RankingPolicyResolver
{
    /**
     * هذا الكلاس مسؤول عن اختيار الـ policy الصحيحة
     * حسب التاب المطلوب.
     *
     * مهم:
     * نحن نفصل "اختيار السياسة" عن "تنفيذ السياسة"
     * حتى يبقى TestDiscoveryService نظيفًا.
     */
    public function __construct(
        private readonly TrendingRankingPolicy $trendingRankingPolicy,
        private readonly NewRankingPolicy $newRankingPolicy,
        private readonly MostParticipatedRankingPolicy $mostParticipatedRankingPolicy,
        private readonly FreeRankingPolicy $freeRankingPolicy,
    )
    {}

    public function resolve(DiscoveryTab $tab): RankingPolicy
    {
        return match ($tab) {
            DiscoveryTab::TRENDING          => $this->trendingRankingPolicy,
            DiscoveryTab::NEW               => $this->newRankingPolicy,
            DiscoveryTab::MOST_PARTICIPATED => $this->mostParticipatedRankingPolicy,
            DiscoveryTab::FREE              => $this->freeRankingPolicy,
        };
    }
}
