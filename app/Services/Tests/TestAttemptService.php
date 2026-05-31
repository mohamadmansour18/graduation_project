<?php

namespace App\Services\Tests;

use App\Enums\TestReviewStatus;
use App\Exceptions\Api\TestException;
use App\Repositories\Tests\TestAttemptRepository;
use Illuminate\Support\Facades\DB;

class TestAttemptService
{
    public function __construct(
        private readonly TestAttemptRepository $repository,
    ) {}

    public function registerAttempt(int $testId, int $userId, string $mode): void
    {
        DB::transaction(function () use ($testId, $userId, $mode) {
            $test = $this->repository->findTestForAttemptWithLock($testId);

            if (! $test) {
                throw TestException::NotFound();
            }

            if ($test->review_status === TestReviewStatus::Deleted->value) {
                throw TestException::testCannotBeAccessed();
            }

            $hadAnyAttemptBefore = $this->repository->userHasAnyAttempt(
                testId: $testId,
                userId: $userId
            );

            $existingAttempt = $this->repository->findAttempt(
                testId: $testId,
                userId: $userId,
                mode: $mode
            );

            if ($existingAttempt) {
                $this->repository->touchAttempt(
                    testId: $testId,
                    userId: $userId,
                    mode: $mode
                );

                return;
            }

            $this->repository->createAttempt(
                testId: $testId,
                userId: $userId,
                mode: $mode
            );

            if (! $hadAnyAttemptBefore) {
                $test->participants_count = ((int) $test->participants_count) + 1;
                $test->save();
            }
        });
    }
}
