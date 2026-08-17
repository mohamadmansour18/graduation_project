<?php

namespace App\Jobs;

use App\Exceptions\Api\ApiException;
use App\Repositories\AiQuestionGeneration\AiQuestionGenerationRepository;
use App\Services\AiQuestionGeneration\AiQuestionGenerationFileStorageService;
use App\Services\AiQuestionGeneration\AiQuestionGenerationProviderOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAiQuestionGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 240;

    public function __construct(
        private readonly int $generationRequestId
    )
    {
        $this->onQueue('heavy');
    }

    public function handle(
        AiQuestionGenerationRepository $repository,
        AiQuestionGenerationFileStorageService $fileStorageService,
        AiQuestionGenerationProviderOrchestrator $providerOrchestrator
    ): void
    {
        $startedAt = hrtime(true);

        Log::info('AI question generation job handling started.', $this->jobLogContext([
            'elapsed_ms' => 0,
        ]));

        $generationRequest = $repository->findWithAssetsById($this->generationRequestId);

        if (! $generationRequest) {
            Log::warning('AI question generation job stopped because the request was not found.', $this->jobLogContext([
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            ]));

            return;
        }

        if ($generationRequest->status !== 'Pending') {
            Log::info('AI question generation job skipped because the request is not pending.', $this->jobLogContext([
                'request_status' => $generationRequest->status,
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            ]));

            return;
        }

        $repository->markAsProcessing($generationRequest);

        Log::info('AI question generation request marked as processing.', $this->jobLogContext([
            'request_status' => 'Processing',
            'assets_count' => $generationRequest->assets->count(),
            'source_type' => $generationRequest->source_type,
            'requested_question_count' => $generationRequest->requested_question_count,
            'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
        ]));

        try {
//            $provider = $providerManager->default();
//
//            $result = $provider->generate($generationRequest);
            Log::info('AI question generation provider orchestration started.', $this->jobLogContext([
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            ]));

            $result = $providerOrchestrator->generate($generationRequest);

            Log::info('AI question generation provider orchestration completed.', $this->jobLogContext([
                'provider' => $result['provider'] ?? null,
                'model' => $result['model'] ?? null,
                'input_mode' => $result['input_mode'] ?? null,
                'questions_count' => count($result['questions'] ?? []),
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            ]));

            $repository->markAsCompleted(
                generationRequest: $generationRequest,
                questions: $result['questions'],
                provider: $result['provider'],
                model: $result['model']
            );

            Log::info('AI question generation request marked as completed.', $this->jobLogContext([
                'request_status' => 'Completed',
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            ]));

            $fileStorageService->deleteStoredAssets(
                $generationRequest->fresh('assets')
            );

            Log::info('AI question generation temporary assets cleanup completed.', $this->jobLogContext([
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            ]));

        } catch (\Throwable $exception) {
            Log::channel('errors')->error('AI question generation job execution failed.', $this->jobLogContext([
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            ]));

            $generationRequest = $repository->findWithAssetsById($this->generationRequestId);

            if ($generationRequest) {
                $failureCode = 'AI_GENERATION_FAILED';
                $failureMessage = 'فشل توليد الأسئلة، يرجى المحاولة لاحقاً';

                if ($exception instanceof ApiException) {

                    $failureCode = $exception->getContext()['failure_code']
                        ?? 'AI_GENERATION_FAILED';

                    $failureMessage = $exception->getMessages();
                }

                $repository->markAsFailed(
                    generationRequest: $generationRequest,
                    failureCode: $failureCode,
                    failureMessage: $failureMessage
                );

                Log::channel('errors')->error('AI question generation request marked as failed after execution exception.', $this->jobLogContext([
                    'failure_code' => $failureCode,
                    'request_status' => 'Failed',
                    'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
                ]));
            }

            throw $exception;

        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('errors')->error('AI question generation job permanently failed.', $this->jobLogContext([
            'exception_class' => $exception::class,
            'error' => $exception->getMessage(),
        ]));

        $repository = app(AiQuestionGenerationRepository::class);

        $generationRequest = $repository->findWithAssetsById($this->generationRequestId);

        if (! $generationRequest) {
            Log::channel('errors')->error('AI question generation failed hook could not find the request.', $this->jobLogContext());

            return;
        }

        if ($generationRequest->status === 'Completed' || $generationRequest->status === 'Failed') {
            Log::info('AI question generation failed hook skipped a terminal request.', $this->jobLogContext([
                'request_status' => $generationRequest->status,
            ]));

            return;
        }

        $failureCode = 'AI_GENERATION_JOB_FAILED';
        $failureMessage = 'فشل توليد الأسئلة بسبب انتهاء وقت المعالجة، يرجى المحاولة لاحقاً';

        if ($exception instanceof ApiException) {
            $failureCode = $exception->getContext()['failure_code']
                ?? $failureCode;

            $failureMessage = $exception->getMessages();
        }

        $repository->markAsFailed(
            generationRequest: $generationRequest,
            failureCode: $failureCode,
            failureMessage: $failureMessage
        );

        Log::channel('errors')->error('AI question generation failed hook marked the request as failed.', $this->jobLogContext([
            'failure_code' => $failureCode,
            'request_status' => 'Failed',
        ]));
    }

    private function jobLogContext(array $context = []): array
    {
        return array_merge([
            'generation_request_id' => $this->generationRequestId,
            'job_uuid' => $this->job?->uuid(),
            'queue_connection' => $this->job?->getConnectionName() ?? config('queue.default'),
            'queue_name' => $this->job?->getQueue() ?? $this->queue,
            'attempt' => $this->job?->attempts(),
            'job_timeout_seconds' => $this->timeout,
            'job_tries' => $this->tries,
            'redis_retry_after_seconds' => config('queue.connections.redis.retry_after'),
        ], $context);
    }

    private function elapsedMilliseconds(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
    }
}
