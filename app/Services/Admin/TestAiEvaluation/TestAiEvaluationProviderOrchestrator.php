<?php

namespace App\Services\Admin\TestAiEvaluation;

use App\Exceptions\Api\TestAiEvaluationException;
use App\Models\TestAiEvaluationRequest;
use App\Services\AiQuestionGeneration\Support\AiProviderFailureClassifier;
use App\Services\AiQuestionGeneration\Support\AiProviderHealthService;
use Illuminate\Support\Facades\Log;
use Throwable;

class TestAiEvaluationProviderOrchestrator
{
    public function __construct(
        private readonly TestAiEvaluationProviderManager $providerManager,
        private readonly AiProviderFailureClassifier $failureClassifier,
        private readonly AiProviderHealthService $providerHealthService
    ) {}

    /**
     * @throws Throwable
     */
    public function evaluate(TestAiEvaluationRequest $evaluationRequest): array
    {
        $providerNames = config('test_ai_evaluation.provider_chain', ['gemini']);
        $providerEntries = $this->providerManager->namedChain(is_array($providerNames) ? $providerNames : ['gemini']);

        $lastException = null;
        $attemptedProviderCount = 0;

        foreach ($providerEntries as $index => $entry) {
            $providerName = $entry['name'];
            $provider = $entry['provider'];
            $healthProviderName = "test_ai_evaluation:{$providerName}";

            if (! $this->providerHealthService->isAvailable($healthProviderName)) {
                Log::info('AI test evaluation provider skipped because it is in cooldown.', [
                    'evaluation_request_id' => $evaluationRequest->id,
                    'provider_name' => $providerName,
                    'health_reason' => $this->providerHealthService->unavailableReason($healthProviderName),
                ]);

                continue;
            }

            $attemptedProviderCount++;

            try {
                Log::info('AI test evaluation provider attempt started.', [
                    'evaluation_request_id' => $evaluationRequest->id,
                    'provider_name' => $providerName,
                    'attempt_index' => $attemptedProviderCount,
                ]);

                $result = $provider->evaluate($evaluationRequest);

                $this->providerHealthService->markAvailable($healthProviderName);

                return $result;
            } catch (Throwable $exception) {
                $lastException = $exception;
                $decision = $this->failureClassifier->classify($exception);

                if ($decision->cooldownSeconds !== null && $decision->cooldownSeconds > 0) {
                    $this->providerHealthService->markUnavailable(
                        providerName: $healthProviderName,
                        failureCode: $decision->failureCode,
                        failureMessage: $decision->failureMessage,
                        cooldownSeconds: $decision->cooldownSeconds
                    );
                }

                $hasNextAvailableProvider = $this->hasNextAvailableProvider($providerEntries, $index);

                Log::channel('errors')->warning('AI test evaluation provider attempt failed.', [
                    'evaluation_request_id' => $evaluationRequest->id,
                    'provider_name' => $providerName,
                    'failure_code' => $decision->failureCode,
                    'failure_message' => $decision->failureMessage,
                    'should_try_next_provider' => $decision->shouldTryNextProvider,
                    'has_next_available_provider' => $hasNextAvailableProvider,
                    'exception_class' => $exception::class,
                    'exception_message' => $exception->getMessage(),
                ]);

                if (! $decision->shouldTryNextProvider || ! $hasNextAvailableProvider) {
                    throw $exception;
                }
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        if ($attemptedProviderCount === 0) {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: 'AI Provider',
                operation: 'orchestrator',
                reason: 'All AI test evaluation providers are temporarily unavailable.'
            );
        }

        throw TestAiEvaluationException::providerInvalidResponse(
            provider: 'AI Provider',
            operation: 'orchestrator',
            reason: 'Provider chain finished without result.'
        );
    }

    private function hasNextAvailableProvider(array $providerEntries, int $currentIndex): bool
    {
        foreach ($providerEntries as $index => $entry) {
            if ($index <= $currentIndex) {
                continue;
            }

            if ($this->providerHealthService->isAvailable("test_ai_evaluation:{$entry['name']}")) {
                return true;
            }
        }

        return false;
    }
}
