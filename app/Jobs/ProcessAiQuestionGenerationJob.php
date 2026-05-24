<?php

namespace App\Jobs;

use App\Exceptions\Api\ApiException;
use App\Repositories\AiQuestionGeneration\AiQuestionGenerationRepository;
use App\Services\AiQuestionGeneration\AiQuestionGenerationFileStorageService;
use App\Services\AiQuestionGeneration\AiQuestionGenerationProviderManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAiQuestionGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;
    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $generationRequestId
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(
        AiQuestionGenerationRepository $repository,
        AiQuestionGenerationFileStorageService $fileStorageService,
        AiQuestionGenerationProviderManager $providerManager
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
            $provider = $providerManager->default();

            $result = $provider->generate($generationRequest);

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
            }

            throw $exception;

        };
    }
}
