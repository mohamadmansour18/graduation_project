<?php

namespace App\Contracts\TestAiEvaluation;

use App\Models\TestAiEvaluationRequest;

interface TestAiEvaluationProviderInterface
{
    /**
     * @return array{
     *     provider: string,
     *     model: string,
     *     result: array{
     *         score_percentage: int,
     *         correct_questions: string,
     *         suspicious_questions: string,
     *         issues: array<int, array{question_position: int, problem: string}>
     *     },
     *     raw_response: array
     * }
     */
    public function evaluate(TestAiEvaluationRequest $evaluationRequest): array;
}
