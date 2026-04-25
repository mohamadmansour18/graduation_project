<?php

namespace App\Services\TestDiscovery\DTO;

use App\Services\TestDiscovery\Enums\DiscoveryScreen;
use App\Services\TestDiscovery\Enums\DiscoveryTab;

final class DiscoveryContextData
{
    public function __construct(
        public readonly DiscoveryScreen $screen,
        public readonly DiscoveryTab $tab,
        public readonly int $limit = 10,
        public readonly ?int $candidatePoolLimit = null,
    ) {
    }

    public function resolvedCandidatePoolLimit(): int
    {
        if ($this->candidatePoolLimit !== null && $this->candidatePoolLimit > 0) {
            return $this->candidatePoolLimit;
        }

        return max(50, $this->limit * 5);       #اذا لم يتم تحديد pool فنضع لها قيمة ابتدائية على الاقل 50 وعلى الاكثر خمسة اضعاف ال limit
    }
}
