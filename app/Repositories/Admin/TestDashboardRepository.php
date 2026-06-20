<?php

namespace App\Repositories\Admin;

use App\Enums\Decision;
use App\Enums\PaymentStatus;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Models\Test;
use App\Models\TestPurchase;
use App\Models\TestQuestion;
use App\Models\TestQuestionOption;
use App\Models\TestReport;
use App\Models\TestReportReasonCounter;
use App\Models\TestReview;
use App\Models\TestReviewRound;
use App\Models\TestRevisionRequest;
use App\Models\TestStatusHistory;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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

    public function createStatusHistory(int $testId, ?int $reviewRoundId, ?TestReviewStatus $fromStatus, TestReviewStatus $toStatus, int $changedByUserId, ?string $note = null): TestStatusHistory
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

    public function findTestForDeletionWithLock(int $testId): ?Test
    {
        return Test::query()
            ->withTrashed()
            ->select([
                'id',
                'creator_user_id',
                'test_type',
                'price',
                'review_status',
                'current_approval_version',
                'published_at',
                'deleted_at',
            ])
            ->whereKey($testId)
            ->lockForUpdate()
            ->first();
    }

    public function hasSuccessfulPurchases(int $testId): bool
    {
        return TestPurchase::query()
            ->where('test_id', $testId)
            ->where('payment_status', PaymentStatus::Paid->value)
            ->exists();
    }

    public function markReviewRoundAsDeleted(TestReviewRound $round, int $reviewerUserId, CarbonInterface $decidedAt): void
    {
        $round->forceFill([
            'reviewer_user_id' => $reviewerUserId,
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

    public function softDeleteTest(Test $test): void
    {
        $test->delete();
    }

    public function forceDeleteTest(Test $test): void
    {
        $test->forceDelete();
    }

    public function findTestForRevisionRequestWithLock(int $testId): ?Test
    {
        return Test::query()
            ->withTrashed()
            ->select([
                'id',
                'creator_user_id',
                'test_type',
                'review_status',
                'current_approval_version',
                'deleted_at',
            ])
            ->whereKey($testId)
            ->lockForUpdate()
            ->first();
    }

    public function findLatestNeedsRevisionRoundForTestWithLock(int $testId): ?TestReviewRound
    {
        return TestReviewRound::query()
            ->where('test_id', $testId)
            ->where('decision', Decision::Needs_Revision->value)
            ->orderByDesc('round_no')
            ->lockForUpdate()
            ->first();
    }

    public function markReviewRoundAsNeedsRevision(TestReviewRound $round, int $reviewerUserId, CarbonInterface $decidedAt): void
    {
        $round->forceFill([
            'reviewer_user_id' => $reviewerUserId,
            'decision' => Decision::Needs_Revision->value,
            'decided_at' => $decidedAt,
        ])->save();
    }

    public function markTestAsNeedsRevision(Test $test): void
    {
        $test->forceFill([
            'review_status' => TestReviewStatus::NeedsRevision,
        ])->save();
    }

    public function countRevisionRequestsForRound(int $roundId): int
    {
        return TestRevisionRequest::query()
            ->where('test_review_round_id', $roundId)
            ->count();
    }

    public function findQuestionIdByPosition(int $testId, int $position): ?int
    {
        return TestQuestion::query()
            ->where('test_id', $testId)
            ->where('position', $position)
            ->value('id');
    }

    public function findOptionIdByQuestionIdAndPosition(int $questionId, int $position): ?int
    {
        return TestQuestionOption::query()
            ->where('test_question_id', $questionId)
            ->where('position', $position)
            ->value('id');
    }

    public function createRevisionRequests(int $testId, int $roundId, int $createdByUserId, array $requests): \Illuminate\Support\Collection
    {
        $now = now();

        $rows = array_map(function (array $request) use ($testId, $roundId, $createdByUserId, $now) {
            return [
                'test_review_round_id' => $roundId,
                'test_id' => $testId,
                'revision_type' => $request['revision_type'],
                'target_question_id' => $request['target_question_id'],
                'target_option_id' => $request['target_option_id'],
                'created_by_user_id' => $createdByUserId,
                'problem_note' => $request['problem_note'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $requests);

        TestRevisionRequest::query()->insert($rows);

        return TestRevisionRequest::query()
            ->where('test_review_round_id', $roundId)
            ->latest('id')
            ->limit(count($rows))
            ->get()
            ->reverse()
            ->values();
    }

    public function findTestWithContent(int $testId, bool $previewOnly = false): Model|Builder|null
    {
        return Test::query()
            ->withTrashed()
            ->select([
                'id',
                'creator_user_id',
            ])
            ->with([
                'testQuestions' => function ($query) use ($previewOnly) {
                    $query
                        ->select([
                            'id',
                            'test_id',
                            'position',
                            'question_text',
                            'hint_text',
                        ])
                        ->when(
                            $previewOnly,
                            fn ($query) => $query->where('is_preview', true)
                        )
                        ->orderBy('position')
                        ->with([
                            'testQuestionOptions' => function ($optionQuery) {
                                $optionQuery
                                    ->select([
                                        'id',
                                        'test_question_id',
                                        'position',
                                        'option_text',
                                        'is_correct',
                                    ])
                                    ->orderBy('position');
                            },
                        ]);
                },
            ])
            ->where('id', $testId)
            ->first();
    }

    public function findTestForReviewsDashboard(int $testId): ?Test
    {
        return Test::query()
            ->withTrashed()
            ->select([
                'id',
                'test_type',
                'average_rating',
                'reviews_count',
                'deleted_at',
            ])
            ->whereKey($testId)
            ->first();
    }

    public function getRatingDistribution(int $testId): \Illuminate\Support\Collection
    {
        return TestReview::query()
            ->select([
                'rating',
                DB::raw('COUNT(*) as count'),
            ])
            ->where('test_id', $testId)
            ->groupBy('rating')
            ->pluck('count', 'rating');
    }

    public function getReviewStatistics(int $testId): object
    {
        return TestReview::query()
            ->where('test_id', $testId)
            ->selectRaw("
            COUNT(*) as ratings_count,
            COUNT(*) as comments_count,
            COALESCE(SUM(helpful_yes_count), 0) as helpful_yes_count,
            COALESCE(SUM(helpful_no_count), 0) as helpful_no_count
        ")
            ->first();
    }

    public function cursorPaginateTestComments(int $testId, ?int $rating, int $perPage): CursorPaginator
    {
        return TestReview::query()
            ->select([
                'id',
                'test_id',
                'user_id',
                'rating',
                'review_text',
                'helpful_yes_count',
                'created_at',
            ])
            ->where('test_id', $testId)
            ->when($rating, function ($query) use ($rating) {
                $query->where('rating', $rating);
            })
            ->with([
                'user:id,name,is_academically_verified',
                'user.userProfile:id,user_id,avatar_path,avatar_disk',
            ])
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }

    public function findReviewForDashboardDeletion(int $reviewId): ?TestReview
    {
        return TestReview::query()
            ->select([
                'id',
                'test_id',
                'user_id',
                'rating',
                'created_at',
            ])
            ->whereKey($reviewId)
            ->first();
    }

    public function findTestForReviewDeletionWithLock(int $testId): ?Test
    {
        return Test::query()
            ->withTrashed()
            ->select([
                'id',
                'creator_user_id',
                'test_type',
                'average_rating',
                'reviews_count',
                'deleted_at',
            ])
            ->whereKey($testId)
            ->lockForUpdate()
            ->first();
    }

    public function findReviewByIdWithLock(int $reviewId): ?TestReview
    {
        return TestReview::query()
            ->select([
                'id',
                'test_id',
                'user_id',
                'rating',
                'created_at',
            ])
            ->whereKey($reviewId)
            ->lockForUpdate()
            ->first();
    }

    public function deleteReview(TestReview $review): void
    {
        $review->delete();
    }

    public function recalculateTestReviewCounters(int $testId): object
    {
        return TestReview::query()
            ->where('test_id', $testId)
            ->selectRaw('COUNT(*) as reviews_count, COALESCE(AVG(rating), 0) as average_rating')
            ->first();
    }

    public function updateTestReviewCounters(Test $test, int $reviewsCount, float $averageRating): void
    {
        $test->forceFill([
            'reviews_count' => $reviewsCount,
            'average_rating' => round($averageRating, 2),
        ])->save();
    }

    public function findTestStatusHistoryForDashboard(int $testId): ?Test
    {
        return Test::query()
            ->withTrashed()
            ->select([
                'id',
                'creator_user_id',
                'test_type',
                'review_status',
                'current_approval_version',
                'deleted_at',
                'created_at',
            ])
            ->whereKey($testId)
            ->with([
                'testStatusHistories' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'test_id',
                            'test_review_round_id',
                            'from_status',
                            'to_status',
                            'changed_by_user_id',
                            'note',
                            'created_at',
                        ])
                        ->orderByDesc('created_at')
                        ->orderByDesc('id');
                },

                'testStatusHistories.changedByUser:id,role_id,name',
                'testStatusHistories.changedByUser.role:id,name',
                'testStatusHistories.changedByUser.userProfile:id,user_id,avatar_path,avatar_disk',

                'testStatusHistories.reviewRound' => function ($query) {
                    $query->select([
                        'id',
                        'test_id',
                        'round_no',
                        'reviewer_user_id',
                        'trigger_type',
                        'decision',
                        'based_on_approval_version',
                        'started_at',
                        'decided_at',
                    ]);
                },

                'testStatusHistories.reviewRound.reviewerUser:id,role_id,name',
                'testStatusHistories.reviewRound.reviewerUser.role:id,name',
                'testStatusHistories.reviewRound.reviewerUser.userProfile:id,user_id,avatar_path',

                'testStatusHistories.reviewRound.testRevisionRequests' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'test_review_round_id',
                            'test_id',
                            'revision_type',
                            'target_question_id',
                            'target_option_id',
                            'created_by_user_id',
                            'resolved_at',
                            'problem_note',
                            'created_at',
                        ])
                        ->orderBy('id');
                },

                'testStatusHistories.reviewRound.testRevisionRequests.targetQuestion:id,test_id,position',
                'testStatusHistories.reviewRound.testRevisionRequests.targetOption:id,test_question_id,position',

                'testStatusHistories.reviewRound.testRevisionChangeLogs' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'test_review_round_id',
                            'test_id',
                            'revision_request_id',
                            'revision_type',
                            'target_question_id',
                            'target_option_id',
                            'before_value',
                            'after_value',
                            'changed_by_user_id',
                            'created_at',
                        ])
                        ->orderBy('id');
                },

                'testStatusHistories.reviewRound.testRevisionChangeLogs.targetQuestion:id,test_id,position',
                'testStatusHistories.reviewRound.testRevisionChangeLogs.targetOption:id,test_question_id,position',
            ])
            ->first();
    }

    public function findTestForReportsDashboard(int $testId): ?Test
    {
        return Test::query()
            ->withTrashed()
            ->select([
                'id',
                'test_type',
                'review_status',
                'current_approval_version',
                'deleted_at',
            ])
            ->whereKey($testId)
            ->first();
    }

    public function getReportReasonCounters(int $testId, int $approvalVersion): Collection
    {
        return TestReportReasonCounter::query()
            ->select([
                'reason',
                'reporters_count',
            ])
            ->where('test_id', $testId)
            ->where('approval_version', $approvalVersion)
            ->orderByDesc('reporters_count')
            ->get();
    }

    public function getTotalReportsCount(int $testId, int $approvalVersion): int
    {
        return (int) TestReportReasonCounter::query()
            ->where('test_id', $testId)
            ->where('approval_version', $approvalVersion)
            ->sum('reporters_count');
    }

    public function cursorPaginateTestReports(int $testId, int $approvalVersion, int $perPage): CursorPaginator
    {
        return TestReport::query()
            ->select([
                'id',
                'test_id',
                'user_id',
                'reason',
                'description',
                'reported_at',
                'created_at',
            ])
            ->where('test_id', $testId)
            ->where('approval_version', $approvalVersion)
            ->with([
                'user:id,name,is_academically_verified',
                'user.userProfile:id,user_id,avatar_path,avatar_disk',
            ])
            ->orderByDesc('reported_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }

    public function findTestForRevisionRequestsUpdateWithLock(int $testId): ?Test
    {
        return Test::query()
            ->withTrashed()
            ->select([
                'id',
                'creator_user_id',
                'test_type',
                'review_status',
                'deleted_at',
            ])
            ->whereKey($testId)
            ->lockForUpdate()
            ->first();
    }

    public function deleteRevisionRequestsForRound(int $roundId): void
    {
        TestRevisionRequest::query()
            ->where('test_review_round_id', $roundId)
            ->delete();
    }


    public function createRevisionRequests2(int $testId, int $roundId, int $createdByUserId, array $requests): Collection
    {
        $now = now();

        $rows = array_map(function (array $request) use ($testId, $roundId, $createdByUserId, $now) {
            return [
                'test_review_round_id' => $roundId,
                'test_id' => $testId,
                'revision_type' => $request['revision_type'],
                'target_question_id' => $request['target_question_id'],
                'target_option_id' => $request['target_option_id'],
                'created_by_user_id' => $createdByUserId,
                'problem_note' => $request['problem_note'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $requests);

        TestRevisionRequest::query()->insert($rows);

        return TestRevisionRequest::query()
            ->where('test_review_round_id', $roundId)
            ->orderBy('id')
            ->get();
    }
}
