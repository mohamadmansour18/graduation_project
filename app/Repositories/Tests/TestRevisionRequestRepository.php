<?php

namespace App\Repositories\Tests;

use App\Enums\Decision;
use App\Enums\TestType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TestRevisionRequestRepository
{
    public function findOwnedPublicReviewRound(int $testId, int $roundId, int $ownerId): ?object
    {
        return DB::table('test_review_rounds')
            ->join('test', 'test.id', '=', 'test_review_rounds.test_id')
            ->select([
                'test_review_rounds.id',
                'test_review_rounds.test_id',
                'test_review_rounds.round_no',
                'test_review_rounds.reviewer_user_id',
                'test_review_rounds.trigger_type',
                'test_review_rounds.decision',
                'test_review_rounds.based_on_approval_version',
                'test_review_rounds.started_at',
                'test_review_rounds.decided_at',
            ])
            ->where('test_review_rounds.id', $roundId)
            ->where('test_review_rounds.test_id', $testId)
            ->where('test.creator_user_id', $ownerId)
            ->where('test.test_type', TestType::Public->value)
            ->first();
    }

    public function getRevisionRequestsByRoundId(int $roundId): Collection
    {
        return DB::table('test_revision_requests')
            ->leftJoin(
                'test_question',
                'test_question.id',
                '=',
                'test_revision_requests.target_question_id'
            )
            ->leftJoin(
                'test_question_options',
                'test_question_options.id',
                '=',
                'test_revision_requests.target_option_id'
            )
            ->leftJoin('test' , 'test.id' , '=' , 'test_question.test_id')
            ->select([
                'test_revision_requests.id',
                'test_revision_requests.test_review_round_id',
                'test_revision_requests.test_id',
                'test_revision_requests.revision_type',
                'test_revision_requests.target_question_id',
                'test_revision_requests.problem_note',
                'test_revision_requests.created_at',

                'test_question.position as question_position',
                'test_question_options.position as question_option_position',

                'test.question_count',

                DB::raw('exists (
                    select 1
                    from test_revision_change_logs
                    where test_revision_change_logs.revision_request_id = test_revision_requests.id
                ) as user_has_modified'),
            ])
            ->where('test_revision_requests.test_review_round_id', $roundId)
            ->orderBy('test_revision_requests.id')
            ->get();
    }
}
