<?php

namespace App\Contracts\AiQuestionGeneration;

use App\Models\AiQuestionGenerationRequest;

interface AiQuestionGenerationProviderInterface
{
    /**
     *
     * @return array{
     *     provider: string,
     *     model: string,
     *     questions: array<int, array>
     * }
     */
    public function generate(AiQuestionGenerationRequest $generationRequest): array;
}
