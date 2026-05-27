<?php

namespace App\Repositories\Tests;

use App\Enums\Decision;
use App\Enums\TestReviewRoundsTriggerType;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Models\Test;
use App\Models\TestReviewRound;
use App\Models\User;

class TestCreationRepository
{
    public function countPendingPublicTestsForUser(int $userId): int
    {
        return Test::query()
            ->where('creator_user_id', $userId)
            ->where('test_type', TestType::Public->value)
            ->whereIn('review_status', [
                TestReviewStatus::New->value,
                TestReviewStatus::NeedsRevision->value,
                TestReviewStatus::UnderReview->value
            ])
            ->count();
    }

    public function countPrivateTestsCreatedToday(int $userId): int
    {
        return Test::query()
            ->where('creator_user_id', $userId)
            ->where('test_type', TestType::Private->value)
            ->whereBetween('created_at', [
                now()->startOfDay(),
                now()->endOfDay(),
            ])
            ->count();
    }

    public function createTest(User $user, array $data): Test
    {
        return $user->creatorTests()->create($data);
    }

    public function createInterestSelections(Test $test, array $interestIds): void
    {
        $rows = [];

        foreach (array_values($interestIds) as $index => $interestId)
        {
            $rows[] = [
                'interest_id' => $interestId,
                'slot_no' => $index + 1,
            ];
        }

        $test->testIntersetSelections()->createMany($rows);
    }

    public function createQuestionWithOptions(Test $test, array $questionData, int $position): void
    {
        $question = $test->testQuestions()->create([
            'position' => $position,
            'question_text' => $questionData['question_text'],
            'hint_text' => $questionData['hint_text'] ?? null,
            'is_preview' => (bool) ($questionData['is_preview'] ?? false),
            'options_count' => count($questionData['options']),
        ]);

        $optionRows = [];

        foreach (array_values($questionData['options']) as $index => $option) {
            $optionRows[] = [
                'position' => $index + 1,
                'option_text' => $option['option_text'],
                'is_correct' => (bool) $option['is_correct'],
            ];
        }

        $question->testQuestionOptions()->createMany($optionRows);
    }

    public function createInitialReviewRound(Test $test): TestReviewRound
    {
        $latestRoundNo = $test->testReviewRounds()->max('round_no');

        return $test->testReviewRounds()->create([
            'round_no' => ((int) $latestRoundNo) + 1,
            'reviewer_user_id' => null,
            'trigger_type' => TestReviewRoundsTriggerType::Initial_Submission->value,
            'decision' => Decision::Pending,
            'based_on_approval_version' => $test->current_approval_version,
            'started_at' => now(),
            'decided_at' => null,
        ]);
    }

    public function createInitialStatusHistory(Test $test, int $userId): void
    {
        $test->testStatusHistories()->create([
            'test_review_round_id' => null,
            'from_status' => null,
            'to_status' => TestReviewStatus::New->value,
            'changed_by_user_id' => $userId,
        ]);
    }
}
