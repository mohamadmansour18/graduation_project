<?php

namespace App\Services\Admin\TestAiEvaluation;

use App\Enums\TestReviewStatus;
use App\Exceptions\Api\TestAiEvaluationException;
use App\Jobs\ProcessTestAiEvaluationJob;
use App\Models\TestAiEvaluationRequest;
use App\Models\User;
use App\Repositories\Admin\TestAiEvaluationRepository;
use App\Services\Cache\CacheKeys;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class TestAiEvaluationService
{
    public function __construct(
        private readonly TestAiEvaluationRepository $repository,
        private readonly TestAiEvaluationPayloadBuilder $payloadBuilder,
        private readonly TestAiEvaluationHashService $hashService
    ) {}

    public function create(User $user, int $testId): array
    {
        $test = $this->repository->findTestWithQuestions($testId);

        if (! $test || $test->trashed()) {
            throw TestAiEvaluationException::testNotFound();
        }

        $this->ensureTestStatusAllowsAiEvaluation($test->review_status);

        $payload = $this->payloadBuilder->build($test);
        $questionsCount = count($payload['questions']);

        if ($questionsCount === 0) {
            throw TestAiEvaluationException::testHasNoQuestions();
        }

        $contentHash = $this->hashService->hash($payload);
        $cachedRequest = $this->findCachedReusableRequest($testId, $contentHash);

        if ($cachedRequest) {
            return $this->formatCreateResponse($cachedRequest, reused: true, retried: false);
        }

        $existingRequest = $this->repository->findByTestAndContentHash($testId, $contentHash);

        if ($existingRequest) {
            if ($existingRequest->isFailed()) {
                $this->repository->resetFailedRequestForRetry(
                    evaluationRequest: $existingRequest,
                    requestedByUserId: $user->id,
                    inputQuestionsJson: $payload,
                    questionsCount: $questionsCount
                );

                ProcessTestAiEvaluationJob::dispatch($existingRequest->id)
                    ->onQueue(config('test_ai_evaluation.queue_name'));

                $existingRequest->refresh();

                $this->rememberRequest($existingRequest);

                return $this->formatCreateResponse($existingRequest, reused: false, retried: true);
            }

            $this->rememberRequest($existingRequest);

            return $this->formatCreateResponse($existingRequest, reused: true, retried: false);
        }

        try {
            $evaluationRequest = $this->repository->createPendingRequest(
                testId: $testId,
                requestedByUserId: $user->id,
                contentHash: $contentHash,
                questionsCount: $questionsCount,
                inputQuestionsJson: $payload
            );
        } catch (QueryException $exception) {
            $evaluationRequest = $this->repository->findByTestAndContentHash($testId, $contentHash);

            if (! $evaluationRequest) {
                throw $exception;
            }

            $this->rememberRequest($evaluationRequest);

            return $this->formatCreateResponse($evaluationRequest, reused: true, retried: false);
        }

        $this->rememberRequest($evaluationRequest);

        ProcessTestAiEvaluationJob::dispatch($evaluationRequest->id)
            ->onQueue(config('test_ai_evaluation.queue_name'));

        return $this->formatCreateResponse($evaluationRequest, reused: false, retried: false);
    }

    public function show(int $evaluationRequestId): array
    {
        $evaluationRequest = $this->repository->findById($evaluationRequestId);

        if (! $evaluationRequest) {
            throw TestAiEvaluationException::evaluationRequestNotFound();
        }

        if ($evaluationRequest->isCompleted()) {
            $this->rememberRequest($evaluationRequest);
        }

        return $this->formatShowResponse($evaluationRequest);
    }

    private function ensureTestStatusAllowsAiEvaluation(mixed $reviewStatus): void
    {
        $status = $reviewStatus instanceof TestReviewStatus
            ? $reviewStatus
            : TestReviewStatus::from((string) $reviewStatus);

        if (in_array($status, [
            TestReviewStatus::Approved,
            TestReviewStatus::NeedsRevision,
        ], true)) {
            throw TestAiEvaluationException::testStatusDoesNotAllowAiEvaluation();
        }
    }

    private function findCachedReusableRequest(int $testId, string $contentHash): ?TestAiEvaluationRequest
    {
        $cached = Cache::tags(CacheKeys::testAiEvaluationTags($testId))
            ->get(CacheKeys::testAiEvaluation($testId, $contentHash));

        if (! is_array($cached) || ! isset($cached['evaluation_request_id'])) {
            return null;
        }

        $evaluationRequest = $this->repository->findById((int) $cached['evaluation_request_id']);

        if (! $evaluationRequest || (int) $evaluationRequest->test_id !== $testId || $evaluationRequest->content_hash !== $contentHash) {
            CacheKeys::clearTestAiEvaluation($testId, $contentHash);

            return null;
        }

        if ($evaluationRequest->isFailed()) {
            return null;
        }

        return $evaluationRequest;
    }

    private function rememberRequest(TestAiEvaluationRequest $evaluationRequest): void
    {
        Cache::tags(CacheKeys::testAiEvaluationTags((int) $evaluationRequest->test_id))
            ->put(
                CacheKeys::testAiEvaluation((int) $evaluationRequest->test_id, $evaluationRequest->content_hash),
                [
                    'evaluation_request_id' => $evaluationRequest->id,
                    'test_id' => $evaluationRequest->test_id,
                    'content_hash' => $evaluationRequest->content_hash,
                    'status' => $evaluationRequest->status,
                ],
                now()->addDays((int) config('test_ai_evaluation.cache_ttl_days', 30))
            );
    }

    private function formatCreateResponse(TestAiEvaluationRequest $evaluationRequest, bool $reused, bool $retried): array
    {
        return [
            'evaluation_request_id' => $evaluationRequest->id,
            'test_id' => $evaluationRequest->test_id,
            'status' => $evaluationRequest->status,
            'reused' => $reused,
            'retried' => $retried,
        ];
    }

    private function formatShowResponse(TestAiEvaluationRequest $evaluationRequest): array
    {
        return [
            'id' => $evaluationRequest->id,
            'test_id' => $evaluationRequest->test_id,
            'status' => $evaluationRequest->status,
            'questions_count' => $evaluationRequest->questions_count,
            'provider' => $evaluationRequest->provider,
            'model' => $evaluationRequest->model,
            'score_percentage' => $evaluationRequest->isCompleted() ? $evaluationRequest->score_percentage : null,
            'correct_questions' => $evaluationRequest->isCompleted() ? $evaluationRequest->correct_questions_label : null,
            'suspicious_questions' => $evaluationRequest->isCompleted() ? $evaluationRequest->suspicious_questions_label : null,
            'issues' => $evaluationRequest->isCompleted() ? ($evaluationRequest->issues_json ?? []) : null,
            'failure' => $evaluationRequest->isFailed()
                ? [
                    'code' => $evaluationRequest->failure_code,
                    'message' => $evaluationRequest->failure_message,
                ]
                : null,
            'started_at' => $evaluationRequest->started_at?->toDateTimeString(),
            'completed_at' => $evaluationRequest->completed_at?->toDateTimeString(),
            'failed_at' => $evaluationRequest->failed_at?->toDateTimeString(),
        ];
    }
}
