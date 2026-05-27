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
    {}

    public function handle(
        AiQuestionGenerationRepository $repository,
        AiQuestionGenerationFileStorageService $fileStorageService,
        AiQuestionGenerationProviderOrchestrator $providerOrchestrator
    ): void
    {

        Log::channel('errors')->info('AI question generation job started.', [
            'generation_request_id' => $this->generationRequestId,
        ]);

        $generationRequest = $repository->findWithAssetsById($this->generationRequestId);

        if (! $generationRequest) {
            Log::channel('errors')->warning('AI question generation request not found.', [
                'generation_request_id' => $this->generationRequestId,
            ]);
            return;
        }

        Log::channel('errors')->info('AI question generation request loaded.', [
            'generation_request_id' => $generationRequest->id,
            'status' => $generationRequest->status,
            'assets_count' => $generationRequest->assets->count(),
        ]);

        if ($generationRequest->status !== 'Pending') {
            Log::channel('errors')->warning('AI question generation job skipped because request is not pending.', [
                'generation_request_id' => $generationRequest->id,
                'status' => $generationRequest->status,
            ]);
            return;
        }

        $repository->markAsProcessing($generationRequest);

        Log::channel('errors')->info('AI question generation request marked as processing.', [
            'generation_request_id' => $generationRequest->id,
        ]);

        try {
            Log::channel('errors')->info('AI question generation provider orchestration started.', [
                'generation_request_id' => $generationRequest->id,
            ]);
//            $provider = $providerManager->default();
//
//            $result = $provider->generate($generationRequest);
            $result = $providerOrchestrator->generate($generationRequest);

            Log::channel('errors')->info('AI question generation provider orchestration finished.', [
                'generation_request_id' => $generationRequest->id,
                'provider' => $result['provider'] ?? null,
                'model' => $result['model'] ?? null,
                'questions_count' => isset($result['questions']) && is_array($result['questions'])
                    ? count($result['questions'])
                    : null,
            ]);

            $repository->markAsCompleted(
                generationRequest: $generationRequest,
                questions: $result['questions'],
                provider: $result['provider'],
                model: $result['model']
            );

            Log::channel('errors')->info('AI question generation request marked as completed.', [
                'generation_request_id' => $generationRequest->id,
            ]);

            $fileStorageService->deleteStoredAssets(
                $generationRequest->fresh('assets')
            );

            Log::channel('errors')->info('AI question generation stored assets deleted.', [
                'generation_request_id' => $generationRequest->id,
            ]);

        } catch (\Throwable $exception) {

            Log::channel('errors')->error('AI question generation job failed.', [
                'generation_request_id' => $this->generationRequestId,
                'error' => $exception->getMessage(),
            ]);

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

                Log::channel('errors')->info('AI question generation request marked as failed.', [
                    'generation_request_id' => $generationRequest->id,
                    'failure_code' => $failureCode,
                    'failure_message' => $failureMessage,
                ]);
            }

            throw $exception;

        };
    }
    public function failed(\Throwable $exception): void
    {
        Log::channel('errors')->error('AI question generation job permanently failed.', [
            'generation_request_id' => $this->generationRequestId,
            'exception_class' => $exception::class,
            'error' => $exception->getMessage(),
        ]);

        $repository = app(AiQuestionGenerationRepository::class);

        $generationRequest = $repository->findWithAssetsById($this->generationRequestId);

        if (! $generationRequest) {
            return;
        }

        if ($generationRequest->status === 'Completed' || $generationRequest->status === 'Failed') {
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
    }
}
