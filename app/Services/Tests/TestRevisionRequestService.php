<?php

namespace App\Services\Tests;

use App\Enums\TestReviewStatus;
use App\Exceptions\Api\TestException;
use App\Repositories\Tests\TestRevisionRequestRepository;

class TestRevisionRequestService
{
    public function __construct(
        private readonly TestRevisionRequestRepository $repository
    ) {}

    public function getByRoundForOwner(int $testId, int $roundId, int $ownerId): \Illuminate\Support\Collection
    {
        $round = $this->repository->findOwnedPublicReviewRound(
            testId: $testId,
            roundId: $roundId,
            ownerId: $ownerId
        );

        if (! $round) {
            throw TestException::revisionRequestsNotAvailable();
        }

        return $this->repository->getRevisionRequestsByRoundId(roundId: $roundId);

    }
}
