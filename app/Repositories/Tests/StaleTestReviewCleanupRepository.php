<?php

namespace App\Repositories\Tests;

use App\Enums\Decision;
use App\Enums\PaymentStatus;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Models\Test;
use App\Models\TestReviewRound;
use App\Models\TestStatusHistory;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class StaleTestReviewCleanupRepository
{
    public function staleCandidateIds(CarbonInterface $cutoff, int $limit): array
    {
        $targetStatuses = [
            TestReviewStatus::New->value,
            TestReviewStatus::Reported->value,
            TestReviewStatus::NeedsRevision->value,
        ];

        $latestStatusHistorySubQuery = DB::table('test_status_histories')
            ->select([
                'test_id',
                DB::raw('MAX(id) as latest_status_history_id'),
            ])
            ->groupBy('test_id');

        return DB::table('test')
            ->select('test.id')
            ->joinSub($latestStatusHistorySubQuery, 'latest_status_history', function ($join) {
                $join->on('latest_status_history.test_id', '=', 'test.id');
            })
            ->join('test_status_histories as current_status_history', function ($join) {
                $join->on(
                    'current_status_history.id',
                    '=',
                    'latest_status_history.latest_status_history_id',
                );
            })
            ->whereNull('test.deleted_at')
            ->where('test.test_type', TestType::Public->value)
            ->whereIn('test.review_status', $targetStatuses)
            ->whereIn('current_status_history.to_status', $targetStatuses)
            ->whereColumn('test.review_status', 'current_status_history.to_status')
            ->where('current_status_history.created_at', '<=', $cutoff)
            ->orderBy('current_status_history.created_at')
            ->orderBy('test.id')
            ->limit($limit)
            ->pluck('test.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function findCandidateForUpdate(int $testId, CarbonInterface $cutoff): ?Test
    {
        $targetStatuses = [
            TestReviewStatus::New->value,
            TestReviewStatus::Reported->value,
            TestReviewStatus::NeedsRevision->value,
        ];

        $latestStatusHistorySubQuery = DB::table('test_status_histories')
            ->select([
                'test_id',
                DB::raw('MAX(id) as latest_status_history_id'),
            ])
            ->groupBy('test_id');

        return Test::query()
            ->select([
                'test.*',
                'current_status_history.id as current_status_history_id',
                'current_status_history.created_at as current_status_changed_at',
            ])
            ->joinSub($latestStatusHistorySubQuery, 'latest_status_history', function ($join) {
                $join->on('latest_status_history.test_id', '=', 'test.id');
            })
            ->join('test_status_histories as current_status_history', function ($join) {
                $join->on(
                    'current_status_history.id',
                    '=',
                    'latest_status_history.latest_status_history_id',
                );
            })
            ->whereKey($testId)
            ->whereNull('test.deleted_at')
            ->where('test.test_type', TestType::Public->value)
            ->whereIn('test.review_status', $targetStatuses)
            ->whereIn('current_status_history.to_status', $targetStatuses)
            ->whereColumn('test.review_status', 'current_status_history.to_status')
            ->where('current_status_history.created_at', '<=', $cutoff)
            ->lockForUpdate()
            ->first();
    }

    public function hasPaidPurchases(int $testId): bool
    {
        return DB::table('test_purchases')
            ->where('test_id', $testId)
            ->where('payment_status', PaymentStatus::Paid->value)
            ->exists();
    }

    public function findLatestOpenReviewRoundForUpdate(int $testId): ?TestReviewRound
    {
        return TestReviewRound::query()
            ->where('test_id', $testId)
            ->whereNull('decided_at')
            ->orderByDesc('round_no')
            ->lockForUpdate()
            ->first();
    }

    public function closeReviewRoundAsDeleted(TestReviewRound $round, CarbonInterface $decidedAt): void
    {
        $round->forceFill([
            'decision' => Decision::Deleted->value,
            'decided_at' => $decidedAt,
        ])->save();
    }

    public function markTestAsDeleted(Test $test): void
    {
        $test->forceFill([
            'review_status' => TestReviewStatus::Deleted->value,
        ])->save();
    }

    public function createStatusHistory(
        int $testId,
        ?int $reviewRoundId,
        TestReviewStatus $fromStatus,
        TestReviewStatus $toStatus,
        ?string $note = null,
    ): TestStatusHistory {
        return TestStatusHistory::query()->create([
            'test_id' => $testId,
            'test_review_round_id' => $reviewRoundId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_user_id' => null,
            'note' => $note,
        ]);
    }

    public function softDelete(Test $test): void
    {
        $test->delete();
    }

    public function forceDelete(Test $test): void
    {
        $test->forceDelete();
    }
}
