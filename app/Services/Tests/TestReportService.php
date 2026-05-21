<?php

namespace App\Services\Tests;

use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Exceptions\Api\TestException;
use App\Repositories\Tests\TestReportRepository;
use App\Support\TestReportThresholdPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestReportService
{
    public function __construct(
        private readonly TestReportRepository $testReportRepository,
        private readonly TestReportThresholdPolicy $thresholdPolicy
    ) {}

    public function store(int $testId, int $reporterUserId, string $reason, ?string $description) : array
    {
        $test = $this->testReportRepository->findReportableTestSnapshot($testId);

        if(! $test)
        {
            throw TestException::notFound();
        }

        $this->ensureTestCanBeReported(
            test: $test,
            reporterUserId: $reporterUserId
        );

        if($this->isPaidTest($test))
        {
            $hasPurchased = $this->testReportRepository->hasUserPurchasedTest(
                testId: $testId,
                userId: $reporterUserId
            );

            if (! $hasPurchased) {
                throw TestException::purchaseRequiredForReport();
            }
        }

        $approvalVersion = (int) $test->current_approval_version;

        $alreadyReported = $this->testReportRepository->reportExistsForSameVersion(
            testId: $testId,
            userId: $reporterUserId,
            reason: $reason,
            approvalVersion: $approvalVersion
        );

        if ($alreadyReported)
        {
            throw TestException::alreadyReportedForSameReasonAndVersion();
        }

        $isStatusChanged = DB::transaction(function () use (
            $testId,
            $reporterUserId,
            $reason,
            $description,
            $approvalVersion
        ) {
            $lockedTest = $this->testReportRepository->lockApprovedTestForReport($testId);

            if ($lockedTest->review_status !== TestReviewStatus::Approved->value) {
                throw TestException::testNotAvailableForReport();
            }

            if ((int) $lockedTest->current_approval_version !== $approvalVersion) {
                throw TestException::testVersionChanged();
            }

            $created = $this->testReportRepository->createReportIfMissing(
                testId: $testId,
                userId: $reporterUserId,
                approvalVersion: $approvalVersion,
                reason: $reason,
                description: $description ? trim($description) : null
            );

            if (! $created) {
                throw TestException::alreadyReportedForSameReasonAndVersion();
            }

            $sameReasonReportersCount = $this->testReportRepository->incrementReasonCounter(
                testId: $testId,
                approvalVersion: $approvalVersion,
                reason: $reason
            );

            $totalDistinctReportersCount = $this->testReportRepository->countTotalDistinctReportersForVersion(
                testId: $testId,
                approvalVersion: $approvalVersion
            );

            $shouldMarkAsReported = $this->thresholdPolicy->shouldMarkAsReported(
                participantsCount: (int) $lockedTest->participants_count,
                sameReasonReportersCount: $sameReasonReportersCount,
                totalDistinctReportersCount: $totalDistinctReportersCount
            );

            if (! $shouldMarkAsReported) {
                return;
            }

            $this->testReportRepository->markTestAsReported($testId);

            $this->testReportRepository->createAutoReportReviewRound(
                testId: $testId,
                approvalVersion: $approvalVersion
            );

            $this->testReportRepository->createStatusHistory(
                testId: $testId,
                reviewRoundId: null,
                fromStatus: TestReviewStatus::Approved->value,
                toStatus: TestReviewStatus::Reported->value,
                changedByUserId: null,
                note: $this->buildAutoReportNote(
                    reason: $reason,
                    sameReasonReportersCount: $sameReasonReportersCount,
                    totalDistinctReportersCount: $totalDistinctReportersCount,
                    participantsCount: (int) $lockedTest->participants_count,
                    approvalVersion: $approvalVersion
                )
            );

            Log::channel('audit')->info('Test marked as reported by automatic report threshold.', [
                'test_id' => $testId,
                'approval_version' => $approvalVersion, 'triggered_by_user_id' => $reporterUserId,
                'reason' => $reason,
                'same_reason_reporters_count' => $sameReasonReportersCount,
                'total_distinct_reporters_count' => $totalDistinctReportersCount,
                'participants_count' => (int) $lockedTest->participants_count,
            ]);

            return true;
        });

        return [
            'is_status_changed' => $isStatusChanged ?? false,
        ];
    }

    private function ensureTestCanBeReported(object $test, int $reporterUserId): void
    {
        if ($test->test_type !== TestType::Public->value) {
            throw TestException::privateTestCannotBeReported();
        }

        if ($test->review_status !== TestReviewStatus::Approved->value) {
            throw TestException::testNotAvailableForReport();
        }

        if ((int) $test->creator_user_id === $reporterUserId) {
            throw TestException::cannotReportOwnTest();
        }
    }

    private function isPaidTest(object $test): bool
    {
        return ! is_null($test->price) && (float) $test->price > 0;
    }

    private function buildAutoReportNote(string $reason, int $sameReasonReportersCount, int $totalDistinctReportersCount, int $participantsCount, int $approvalVersion): string
    {
        return sprintf(
            'تم تحويل الاختبار تلقائياً إلى reported بسبب وصول البلاغات إلى العتبة. السبب: %s | عدد بلاغات نفس السبب: %d | إجمالي المبلغين: %d | عدد المتقدمين: %d | نسخة الاعتماد: %d',
            $reason,
            $sameReasonReportersCount,
            $totalDistinctReportersCount,
            $participantsCount,
            $approvalVersion
        );
    }
}
