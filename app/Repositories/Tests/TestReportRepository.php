<?php

namespace App\Repositories\Tests;

use App\Enums\Decision;
use App\Enums\PaymentStatus;
use App\Enums\TestReviewRoundsTriggerType;
use App\Enums\TestReviewStatus;
use Illuminate\Support\Facades\DB;

class TestReportRepository
{
    public function findReportableTestSnapshot(int $testId): ?object
    {
        return DB::table('test')
            ->select([
                'id',
                'creator_user_id',
                'test_type',
                'review_status',
                'price',
                'current_approval_version',
                'participants_count',
            ])
            ->where('id', $testId)
            ->first();
    }

    public function hasUserPurchasedTest(int $testId, int $userId): bool
    {
        return DB::table('test_purchases')
            ->where('test_id', $testId)
            ->where('buyer_user_id', $userId)
            ->where('payment_status', PaymentStatus::Paid->value)
            ->exists();
    }

    public function reportExistsForSameVersion(int $testId, int $userId, string $reason, int $approvalVersion): bool
    {
        return DB::table('test_reports')
            ->where('test_id', $testId)
            ->where('user_id', $userId)
            ->where('reason', $reason)
            ->where('approval_version', $approvalVersion)
            ->exists();
    }

    public function lockApprovedTestForReport(int $testId): ?object
    {
        return DB::table('test')
            ->select([
                'id',
                'creator_user_id',
                'review_status',
                'current_approval_version',
                'participants_count',
            ])
            ->where('id', $testId)
            ->lockForUpdate()
            ->first();
    }

    public function createReportIfMissing(int $testId, int $userId, int $approvalVersion, string $reason, ?string $description): bool
    {
        $now = now();

        $inserted = DB::table('test_reports')->insertOrIgnore([
            'test_id' => $testId,
            'user_id' => $userId,
            'approval_version' => $approvalVersion,
            'reason' => $reason,
            'description' => $description,
            'reported_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $inserted === 1;
    }

    public function incrementReasonCounter(int $testId, int $approvalVersion, string $reason): int
    {
        $now = now();

        DB::table('test_report_reason_counters')->insertOrIgnore([
            'test_id' => $testId,
            'approval_version' => $approvalVersion,
            'reason' => $reason,
            'reporters_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('test_report_reason_counters')
            ->where('test_id', $testId)
            ->where('approval_version', $approvalVersion)
            ->where('reason', $reason)
            ->increment('reporters_count', 1, [
                'updated_at' => $now,
            ]);

        return (int) DB::table('test_report_reason_counters')
            ->where('test_id', $testId)
            ->where('approval_version', $approvalVersion)
            ->where('reason', $reason)
            ->value('reporters_count');
    }

    public function countTotalDistinctReportersForVersion(int $testId, int $approvalVersion): int
    {
        return (int) DB::table('test_reports')
            ->where('test_id', $testId)
            ->where('approval_version', $approvalVersion)
            ->distinct('user_id')
            ->count('user_id');
    }

    public function markTestAsReported(int $testId): void
    {
        DB::table('test')
            ->where('id', $testId)
            ->update([
                'review_status' => TestReviewStatus::Reported->value,
                'updated_at' => now(),
            ]);
    }

    public function getNextReviewRoundNumber(int $testId): int
    {
        $lastRoundNo = DB::table('test_review_rounds')
            ->where('test_id', $testId)
            ->max('round_no');

        return ((int) $lastRoundNo) + 1;
    }

    public function createAutoReportReviewRound(int $testId, int $approvalVersion): int
    {
        $now = now();

        return (int) DB::table('test_review_rounds')->insertGetId([
            'test_id' => $testId,
            'round_no' => $this->getNextReviewRoundNumber($testId),
            'reviewer_user_id' => null,
            'trigger_type' => TestReviewRoundsTriggerType::Auto_Reported->value,
            'decision' => Decision::Pending->value,
            'based_on_approval_version' => $approvalVersion,
            'started_at' => $now,
            'decided_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function createStatusHistory(int $testId, ?int $reviewRoundId , string $fromStatus, string $toStatus, ?int $changedByUserId, string $note): void
    {
        $now = now();

        DB::table('test_status_histories')->insert([
            'test_id' => $testId,
            'test_review_round_id' => $reviewRoundId ,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_user_id' => $changedByUserId,
            'note' => $note,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
