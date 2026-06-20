<?php

namespace App\Services\Admin\TestAiEvaluation;

use App\Contracts\TestAiEvaluation\TestAiEvaluationProviderInterface;
use RuntimeException;

class TestAiEvaluationProviderManager
{
    /**
     * @param array<int, string> $providerNames
     * @return array<int, array{name: string, provider: TestAiEvaluationProviderInterface}>
     */
    public function namedChain(array $providerNames): array
    {
        $providers = [];

        foreach ($providerNames as $providerName) {
            $providerName = trim((string) $providerName);

            if ($providerName === '') {
                continue;
            }

            $providers[] = [
                'name' => $providerName,
                'provider' => $this->provider($providerName),
            ];
        }

        if ($providers === []) {
            throw new RuntimeException('AI test evaluation provider chain is empty.');
        }

        return $providers;
    }

    private function provider(string $name): TestAiEvaluationProviderInterface
    {
        $providerClass = config("test_ai_evaluation.providers.{$name}");

        if (! is_string($providerClass) || $providerClass === '') {
            throw new RuntimeException("AI test evaluation provider [{$name}] is not configured.");
        }

        $provider = app($providerClass);

        if (! $provider instanceof TestAiEvaluationProviderInterface) {
            throw new RuntimeException("AI test evaluation provider [{$name}] must implement TestAiEvaluationProviderInterface.");
        }

        return $provider;
    }
}
