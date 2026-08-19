<?php

namespace App\Repositories\Admin;

use App\Models\Test;
use App\Models\TestAiEvaluationRequest;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TestAiEvaluationRepository
{
    public const string STATUS_PENDING = 'Pending';
    public const string STATUS_PROCESSING = 'Processing';
    public const string STATUS_COMPLETED = 'Completed';
    public const string STATUS_FAILED = 'Failed';

    public function findTestWithQuestions(int $testId): Test|Builder|null
    {
        return Test::query()
            ->withTrashed()
            ->select([
                'id',
                'difficulty_level',
                'language',
                'review_status',
                'question_count',
                'deleted_at',
            ])
            ->with([
                'testQuestions' => function ($query) {
                    $query
                        ->select([
                            'id',
                            'test_id',
                            'position',
                            'question_text',
                        ])
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
            ->whereKey($testId)
            ->first();
    }

    public function findByTestAndContentHash(int $testId, string $contentHash): Builder|TestAiEvaluationRequest|null
    {
        return TestAiEvaluationRequest::query()
            ->where('test_id', $testId)
            ->where('content_hash', $contentHash)
            ->latest('id')
            ->first();
    }

    public function findById(int $id): Builder|TestAiEvaluationRequest|null
    {
        return TestAiEvaluationRequest::query()
            ->whereKey($id)
            ->first();
    }

    public function createPendingRequest(int $testId, ?int $requestedByUserId, string $contentHash, int $questionsCount, array $inputQuestionsJson): TestAiEvaluationRequest
    {
        return TestAiEvaluationRequest::query()->create([
            'test_id' => $testId,
            'requested_by_user_id' => $requestedByUserId,
            'status' => self::STATUS_PENDING,
            'content_hash' => $contentHash,
            'questions_count' => $questionsCount,
            'input_questions_json' => $inputQuestionsJson,
        ]);
    }

    public function resetFailedRequestForRetry(TestAiEvaluationRequest $evaluationRequest, ?int $requestedByUserId, array $inputQuestionsJson, int $questionsCount): void
    {
        $evaluationRequest->forceFill([
            'requested_by_user_id' => $requestedByUserId,
            'status' => self::STATUS_PENDING,
            'questions_count' => $questionsCount,
            'input_questions_json' => $inputQuestionsJson,
            'provider' => null,
            'model' => null,
            'score_percentage' => null,
            'correct_questions_label' => null,
            'suspicious_questions_label' => null,
            'issues_json' => null,
            'raw_response_json' => null,
            'failure_code' => null,
            'failure_message' => null,
            'started_at' => null,
            'completed_at' => null,
            'failed_at' => null,
        ])->save();
    }

    public function markAsProcessing(TestAiEvaluationRequest $evaluationRequest, CarbonInterface $startedAt): bool
    {
        $claimed = TestAiEvaluationRequest::query()
            ->whereKey($evaluationRequest->getKey())
            ->where('status', self::STATUS_PENDING)
            ->update([
                'status' => self::STATUS_PROCESSING,
                'started_at' => $startedAt,
                'failed_at' => null,
                'failure_code' => null,
                'failure_message' => null,
            ]) === 1;

        if ($claimed) {
            $evaluationRequest->refresh();
        }

        return $claimed;
    }

    public function markAsCompleted(TestAiEvaluationRequest $evaluationRequest, array $providerResult, CarbonInterface $completedAt): void
    {
        $result = $providerResult['result'];

        $evaluationRequest->forceFill([
            'status' => self::STATUS_COMPLETED,
            'provider' => $providerResult['provider'],
            'model' => $providerResult['model'],
            'score_percentage' => $result['score_percentage'],
            'correct_questions_label' => $result['correct_questions'],
            'suspicious_questions_label' => $result['suspicious_questions'],
            'issues_json' => $result['issues'],
            'raw_response_json' => $providerResult['raw_response'],
            'completed_at' => $completedAt,
            'failure_code' => null,
            'failure_message' => null,
        ])->save();
    }

    public function markAsFailed(TestAiEvaluationRequest $evaluationRequest, string $failureCode, string $failureMessage, CarbonInterface $failedAt): void
    {
        $evaluationRequest->forceFill([
            'status' => self::STATUS_FAILED,
            'failure_code' => $failureCode,
            'failure_message' => $failureMessage,
            'failed_at' => $failedAt,
        ])->save();
    }
}
