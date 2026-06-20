<?php

namespace App\Services\Admin\TestAiEvaluation;

class TestAiEvaluationHashService
{
    public function hash(array $payload): string
    {
        $hashPayload = [
            'questions' => $payload['questions'] ?? [],
        ];

        return hash(
            'sha256',
            json_encode($hashPayload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );
    }
}
