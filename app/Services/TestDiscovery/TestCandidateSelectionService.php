<?php

namespace App\Services\TestDiscovery;

use App\Repositories\TestDiscovery\TestDiscoveryRepository;
use App\Services\TestDiscovery\DTO\DiscoveryContextData;
use App\Services\TestDiscovery\DTO\UserDiscoveryProfileData;

/**
 * هذا الـ service هو المنسّق البسيط لطبقة المرشحين.
 * اليوم دوره بسيط:
 * - يستقبل UserDiscoveryProfileData
 * - يستقبل DiscoveryContextData
 * - يطلب من الـ repository بناء candidate pool
 *
 * لاحقًا يمكننا أن نضيف هنا:
 * - logging
 * - metrics
 * - cache خفيف
 * - widening strategies أكثر ذكاء
 */
final class TestCandidateSelectionService
{
    public function __construct(
        private readonly TestDiscoveryRepository $testDiscoveryRepository,
    )
    {}

    public function selectCandidates(UserDiscoveryProfileData $userProfile, DiscoveryContextData $context): array {
        return $this->testDiscoveryRepository->findCandidatesForDiscovery(userProfile: $userProfile, context: $context);
    }
}
