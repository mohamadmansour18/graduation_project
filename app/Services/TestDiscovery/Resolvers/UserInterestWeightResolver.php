<?php

namespace App\Services\TestDiscovery\Resolvers;

final class UserInterestWeightResolver
{
    public function resolve(array $interestSelections): array
    {
        $weightedInterests = [];

        foreach ($interestSelections as $selection) {
            $interestId = (int) ($selection['interest_id'] ?? 0);

            if ($interestId <= 0) {
                continue;
            }

            $slotNo = isset($selection['slot_no']) ? (int) $selection['slot_no'] : null;
            $weight = $this->weightForSlot($slotNo);

            $weightedInterests[$interestId] = max($weightedInterests[$interestId] ?? 0 , $weight);
        }

        return $weightedInterests;
    }

    private function weightForSlot(?int $slotNo): int
    {
        return match ($slotNo) {
            1 => 5,
            2 => 4,
            3 => 3,
            4 => 2,
            5 => 1,
            default => 1,
        };
    }

}
