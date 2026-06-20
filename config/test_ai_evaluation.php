<?php

use App\Services\TestAiEvaluation\Providers\CloudflareWorkersAiTestAiEvaluationProvider;
use App\Services\TestAiEvaluation\Providers\GeminiTestAiEvaluationProvider;
use App\Services\TestAiEvaluation\Providers\HuggingFaceTestAiEvaluationProvider;
use App\Services\TestAiEvaluation\Providers\OllamaLocalTestAiEvaluationProvider;
use App\Services\TestAiEvaluation\Providers\OpenRouterTestAiEvaluationProvider;

return [
    'queue_name' => env('AI_QUESTION_GENERATION_QUEUE', 'default'),

    'cache_ttl_days' => 30,

    'provider_chain' => [
//        'gemini',
//        'openrouter',
        'cloudflare_workers_ai',
        'huggingface',
        'ollama_local',
    ],

    'providers' => [
//        'gemini' => GeminiTestAiEvaluationProvider::class,
//        'openrouter' => OpenRouterTestAiEvaluationProvider::class,
        'cloudflare_workers_ai' => CloudflareWorkersAiTestAiEvaluationProvider::class,
        'huggingface' => HuggingFaceTestAiEvaluationProvider::class,
        'ollama_local' => OllamaLocalTestAiEvaluationProvider::class,
    ],
];
