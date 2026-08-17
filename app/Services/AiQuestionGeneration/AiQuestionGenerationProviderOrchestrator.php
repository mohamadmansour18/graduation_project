<?php

namespace App\Services\AiQuestionGeneration;

use App\Exceptions\Api\AiQuestionGenerationException;
use App\Exceptions\Api\ApiException;
use App\Models\AiQuestionGenerationRequest;
use App\Services\AiQuestionGeneration\Routing\AiQuestionGenerationRoutingPolicy;
use App\Services\AiQuestionGeneration\Support\AiProviderFailureClassifier;
use App\Services\AiQuestionGeneration\Support\AiProviderHealthService;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiQuestionGenerationProviderOrchestrator
{
    public function __construct(
        private readonly AiQuestionGenerationProviderManager $providerManager,
        private readonly AiProviderFailureClassifier $failureClassifier,
        private readonly AiProviderHealthService $providerHealthService,
        private readonly AiQuestionGenerationRoutingPolicy $routingPolicy,
    ) {}

    /**
     * @return array{
     *     provider: string,
     *     model: string,
     *     input_mode?: string,
     *     questions: array<int, array>
     * }
     *
     * @throws Throwable
     */
    public function generate(AiQuestionGenerationRequest $generationRequest): array
    {
        $orchestrationStartedAt = hrtime(true);
        $providerNames = $this->routingPolicy->buildProviderChain($generationRequest);

        $providerEntries = $this->providerManager->namedChain($providerNames);

        $lastException = null;
        $attemptedProviderCount = 0;

        foreach ($providerEntries as $index => $entry) {
            $providerName = $entry['name'];
            $provider = $entry['provider'];

            if (! $this->providerHealthService->isAvailable($providerName)) {
                Log::info('AI question generation provider skipped because it is in cooldown.', [
                    'generation_request_id' => $generationRequest->id,
                    'provider_name' => $providerName,
                    'provider_class' => $provider::class,
                    'health_reason' => $this->providerHealthService->unavailableReason($providerName),
                    'orchestration_elapsed_ms' => $this->elapsedMilliseconds($orchestrationStartedAt),
                ]);

                continue;
            }

            $attemptedProviderCount++;
            $attemptStartedAt = hrtime(true);

            try {
                Log::info('AI question generation provider attempt started.', [
                    'generation_request_id' => $generationRequest->id,
                    'provider_name' => $providerName,
                    'provider_class' => $provider::class,
                    'attempt_index' => $attemptedProviderCount,
                    'source_type' => $generationRequest->source_type,
                    'provider_timeout_seconds' => config("ai_question_generation.{$providerName}.timeout_seconds"),
                    'orchestration_elapsed_ms' => $this->elapsedMilliseconds($orchestrationStartedAt),
                ]);

                $result = $provider->generate($generationRequest);

                $this->providerHealthService->markAvailable($providerName);

                Log::info('AI question generation provider succeeded.', [
                    'generation_request_id' => $generationRequest->id,
                    'provider_name' => $providerName,
                    'provider_class' => $provider::class,
                    'provider_label' => $result['provider'] ?? $providerName,
                    'model' => $result['model'] ?? null,
                    'input_mode' => $result['input_mode'] ?? 'unknown',
                    'questions_count' => count($result['questions'] ?? []),
                    'attempted_provider_count' => $attemptedProviderCount,
                    'attempt_elapsed_ms' => $this->elapsedMilliseconds($attemptStartedAt),
                    'orchestration_elapsed_ms' => $this->elapsedMilliseconds($orchestrationStartedAt),
                ]);

                return $result;

            } catch (Throwable $exception) {
                $lastException = $exception;

                $decision = $this->failureClassifier->classify($exception);

                if ($decision->cooldownSeconds !== null && $decision->cooldownSeconds > 0) {
                    $this->providerHealthService->markUnavailable(
                        providerName: $providerName,
                        failureCode: $decision->failureCode,
                        failureMessage: $decision->failureMessage,
                        cooldownSeconds: $decision->cooldownSeconds
                    );
                }

                $hasNextAvailableProvider = $this->hasNextAvailableProvider(
                    providerEntries: $providerEntries,
                    currentIndex: $index
                );

                Log::channel('errors')->warning('AI question generation provider attempt failed.', [
                    'generation_request_id' => $generationRequest->id,
                    'provider_name' => $providerName,
                    'provider_class' => $provider::class,
                    'failure_code' => $decision->failureCode,
                    'failure_message' => $decision->failureMessage,
                    'should_try_next_provider' => $decision->shouldTryNextProvider,
                    'has_next_available_provider' => $hasNextAvailableProvider,
                    'cooldown_seconds' => $decision->cooldownSeconds,
                    'exception_class' => $exception::class,
                    'exception_message' => $exception->getMessage(),
                    'exception_context' => $exception instanceof ApiException
                        ? $exception->getContext()
                        : [],
                    'attempt_elapsed_ms' => $this->elapsedMilliseconds($attemptStartedAt),
                    'orchestration_elapsed_ms' => $this->elapsedMilliseconds($orchestrationStartedAt),
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
            throw AiQuestionGenerationException::allProvidersTemporarilyUnavailable();
        }

        throw AiQuestionGenerationException::providerInvalidResponse(
            provider: 'AI Provider',
            operation: 'orchestrator',
            reason: 'Provider chain finished without result.'
        );
    }

    /**
     * @param array<int, array{name: string, provider: mixed}> $providerEntries
     */
    private function hasNextAvailableProvider(array $providerEntries, int $currentIndex): bool
    {
        foreach ($providerEntries as $index => $entry) {
            if ($index <= $currentIndex) {
                continue;
            }

            if ($this->providerHealthService->isAvailable($entry['name'])) {
                return true;
            }
        }

        return false;
    }

    private function elapsedMilliseconds(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
    }

}
