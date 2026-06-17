<?php

namespace App\Repositories\Admin;

use App\Enums\Decision;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Models\Test;
use App\Models\TestReviewRound;
use App\Models\TestStatusHistory;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TestDashboardRepository
{
    public function getTestsWhoseCurrentStatusChangedBetween(CarbonInterface $startOfDay, CarbonInterface $endOfDay, array $statuses): Collection
    {
        $statusValues = array_map(
            fn (TestReviewStatus $status) => $status->value,
            $statuses
        );

        $latestStatusHistorySubQuery = DB::table('test_status_histories')
            ->select([
                'test_id',
                DB::raw('MAX(id) as latest_status_history_id'),
            ])
            ->groupBy('test_id');

        return Test::query()
            ->withTrashed()
            ->select('test.*')
            ->joinSub($latestStatusHistorySubQuery, 'latest_status_history', function ($join) {
                $join->on('latest_status_history.test_id', '=', 'test.id');
            })
            ->join('test_status_histories as current_status_history', function ($join) {
                $join->on(
                    'current_status_history.id',
                    '=',
                    'latest_status_history.latest_status_history_id'
                );
            })
            ->whereIn('test.review_status', $statusValues)
            ->whereIn('current_status_history.to_status', $statusValues)
            ->whereColumn('test.review_status', 'current_status_history.to_status')
            ->whereBetween('current_status_history.created_at', [
                $startOfDay,
                $endOfDay,
            ])
            ->where('test.test_type' , TestType::Public->value)
            ->with([
                'testIntersetSelections' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'test_id',
                            'interest_id',
                            'slot_no',
                        ])
                        ->orderBy('slot_no');
                },
                'testIntersetSelections.interest:id,name',
            ])
            ->orderByDesc('current_status_history.created_at')
            ->orderByDesc('test.id')
            ->get();
    }

    public function findManagementTestDetails(int $testId): ?Test
    {
        return Test::query()
            ->withTrashed()
            ->select([
                'id',
                'creator_user_id',
                'title',
                'description',
                'duration_seconds',
                'difficulty_level',
                'pass_mark_percentage',
                'published_at',
                'price',
                'review_status',
                'last_content_updated_at',
                'target_level',
                'language',
                'participants_count',
                'question_count',
                'likes_count',
                'reviews_count',
                'bookmarks_count',
                'downloads_count',
                'created_at',
            ])
            ->with([
                'creatorUser:id,name,is_academically_verified',
                'creatorUser.userProfile:id,user_id,avatar_disk,avatar_path',
                'creatorUser.userProfileStat:id,user_id,followers_count,following_count,published_tests_count',

                'testIntersetSelections:id,test_id,interest_id,slot_no',
                'testIntersetSelections.interest:id,name',
            ])
            ->find($testId);
    }

    public function findTestForApprovalWithLock(int $testId): ?Test
    {
        return Test::query()
            ->withTrashed()
            ->select([
                'id',
                'creator_user_id',
                'test_type',
                'review_status',
                'current_approval_version',
                'published_at',
                'deleted_at',
            ])
            ->whereKey($testId)
            ->lockForUpdate()
            ->first();
    }

    public function findPendingReviewRoundForTestWithLock(int $testId): ?TestReviewRound
    {
        return TestReviewRound::query()
            ->where('test_id', $testId)
            ->where('decision', Decision::Pending->value)
            ->orderByDesc('round_no')
            ->lockForUpdate()
            ->first();
    }

    public function approveReviewRound(TestReviewRound $round, int $reviewerUserId, CarbonInterface $decidedAt): void
    {
        $round->forceFill([
            'reviewer_user_id' => $reviewerUserId,
            'decision' => Decision::Approved->value,
            'decided_at' => $decidedAt,
        ])->save();
    }

    public function markTestAsApproved(Test $test, int $newApprovalVersion, ?CarbonInterface $publishedAt): void
    {
        $test->forceFill([
            'review_status' => TestReviewStatus::Approved->value,
            'current_approval_version' => $newApprovalVersion,
            'published_at' => $publishedAt,
        ])->save();
    }

    public function createStatusHistory(int $testId, int $reviewRoundId, ?TestReviewStatus $fromStatus, TestReviewStatus $toStatus, int $changedByUserId, ?string $note = null): TestStatusHistory
    {
        return TestStatusHistory::query()->create([
            'test_id' => $testId,
            'test_review_round_id' => $reviewRoundId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_user_id' => $changedByUserId,
            'note' => $note,
        ]);
    }
}
