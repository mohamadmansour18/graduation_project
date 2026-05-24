<?php

namespace App\Services\AiQuestionGeneration;

use App\Contracts\AiQuestionGeneration\AiQuestionGenerationProviderInterface;
use RuntimeException;

class AiQuestionGenerationProviderManager
{
    public function default(): AiQuestionGenerationProviderInterface
    {
        return $this->provider(
            (string) config('ai_question_generation.default_provider', 'gemini')
        );
    }

    public function provider(string $name): AiQuestionGenerationProviderInterface
    {
        $providerClass = config("ai_question_generation.providers.{$name}");

        if (! is_string($providerClass) || $providerClass === '') {
            throw new RuntimeException("AI question generation provider [{$name}] is not configured.");
        }

        $provider = app($providerClass);

        if (! $provider instanceof AiQuestionGenerationProviderInterface) {
            throw new RuntimeException("AI question generation provider [{$name}] must implement AiQuestionGenerationProviderInterface.");
        }

        return $provider;
    }
}
