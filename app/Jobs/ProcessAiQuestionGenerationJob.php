<?php

namespace App\Jobs;

use App\Contracts\AiQuestionGeneration\AiQuestionGenerationProviderInterface;
use App\Repositories\AiQuestionGeneration\AiQuestionGenerationRepository;
use App\Services\AiQuestionGeneration\AiQuestionGenerationFileStorageService;
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

    public int $timeout = 300;
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
        AiQuestionGenerationProviderInterface $provider
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
            Log::info('AI_JOB_BEFORE_PROVIDER', [
                'generation_request_id' => $generationRequest->id,
                'assets_count' => $generationRequest->assets->count(),
            ]);
            $result = $provider->generate($generationRequest);
            Log::info('AI_JOB_AFTER_PROVIDER', [
                'generation_request_id' => $generationRequest->id,
                'questions_count' => count($result['questions'] ?? []),
                'provider' => $result['provider'] ?? null,
                'model' => $result['model'] ?? null,
            ]);
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
                $repository->markAsFailed(
                    generationRequest: $generationRequest,
                    failureCode: 'AI_GENERATION_FAILED',
                    failureMessage: 'فشل توليد الأسئلة، يرجى المحاولة لاحقاً'
                );
            }

            throw $exception;

        };
    }
}
