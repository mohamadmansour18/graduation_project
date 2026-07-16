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

        $generationRequest = $repository->findWithAssetsById($this->generationRequestId);

        if (! $generationRequest) {
            return;
        }

        if ($generationRequest->status !== 'Pending') {
            return;
        }

        $repository->markAsProcessing($generationRequest);

        try {
//            $provider = $providerManager->default();
//
//            $result = $provider->generate($generationRequest);
            $result = $providerOrchestrator->generate($generationRequest);

            $repository->markAsCompleted(
                generationRequest: $generationRequest,
                questions: $result['questions'],
                provider: $result['provider'],
                model: $result['model']
            );

            $fileStorageService->deleteStoredAssets(
                $generationRequest->fresh('assets')
            );

        } catch (\Throwable $exception) {

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
