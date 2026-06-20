<?php

namespace App\Jobs;

use App\Exceptions\Api\ApiException;
use App\Repositories\Admin\TestAiEvaluationRepository;
use App\Services\Admin\TestAiEvaluation\TestAiEvaluationProviderOrchestrator;
use App\Services\Cache\CacheKeys;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessTestAiEvaluationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 240;

    public function __construct(
        private readonly int $evaluationRequestId
    ) {}

    public function handle(TestAiEvaluationRepository $repository, TestAiEvaluationProviderOrchestrator $providerOrchestrator): void
    {
        $evaluationRequest = $repository->findById($this->evaluationRequestId);

        if (! $evaluationRequest) {
            return;
        }

        if ($evaluationRequest->status !== TestAiEvaluationRepository::STATUS_PENDING) {
            return;
        }

        $repository->markAsProcessing($evaluationRequest, now());

        try {
            $evaluationRequest->refresh();

            $result = $providerOrchestrator->evaluate($evaluationRequest);

            $repository->markAsCompleted(
                evaluationRequest: $evaluationRequest,
                providerResult: $result,
                completedAt: now()
            );

            $evaluationRequest->refresh();
            $this->rememberRequest($evaluationRequest);

        } catch (\Throwable $exception) {

            $evaluationRequest = $repository->findById($this->evaluationRequestId);

            if ($evaluationRequest) {
                [$failureCode, $failureMessage] = $this->failureDetails($exception);

                $repository->markAsFailed(
                    evaluationRequest: $evaluationRequest,
                    failureCode: $failureCode,
                    failureMessage: $failureMessage,
                    failedAt: now()
                );

                CacheKeys::clearTestAiEvaluation(
                    testId: (int) $evaluationRequest->test_id,
                    contentHash: $evaluationRequest->content_hash
                );
            }

            throw $exception;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('errors')->error('AI test evaluation job permanently failed.', [
            'evaluation_request_id' => $this->evaluationRequestId,
            'exception_class' => $exception::class,
            'error' => $exception->getMessage(),
        ]);

        $repository = app(TestAiEvaluationRepository::class);
        $evaluationRequest = $repository->findById($this->evaluationRequestId);

        if (! $evaluationRequest || in_array($evaluationRequest->status, [
            TestAiEvaluationRepository::STATUS_COMPLETED,
            TestAiEvaluationRepository::STATUS_FAILED,
        ], true)) {
            return;
        }

        [$failureCode, $failureMessage] = $this->failureDetails($exception);

        $repository->markAsFailed(
            evaluationRequest: $evaluationRequest,
            failureCode: $failureCode,
            failureMessage: $failureMessage,
            failedAt: now()
        );
    }

    private function failureDetails(\Throwable $exception): array
    {
        if ($exception instanceof ApiException) {
            return [
                $exception->getContext()['failure_code'] ?? 'AI_TEST_EVALUATION_FAILED',
                $exception->getMessages(),
            ];
        }

        return [
            'AI_TEST_EVALUATION_FAILED',
            'فشل تقييم الاختبار باستخدام الذكاء الاصطناعي، يرجى المحاولة لاحقاً',
        ];
    }

    private function rememberRequest($evaluationRequest): void
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
}
