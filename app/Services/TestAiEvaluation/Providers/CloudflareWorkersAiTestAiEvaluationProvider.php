<?php

namespace App\Services\TestAiEvaluation\Providers;

use App\Contracts\TestAiEvaluation\TestAiEvaluationProviderInterface;
use App\Exceptions\Api\TestAiEvaluationException;
use App\Models\TestAiEvaluationRequest;
use App\Services\Admin\TestAiEvaluation\TestAiEvaluationResultNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class CloudflareWorkersAiTestAiEvaluationProvider implements TestAiEvaluationProviderInterface
{
    private string $providerName = 'CloudflareWorkersAI';

    public function __construct(
        private readonly TestAiEvaluationResultNormalizer $normalizer
    ) {}

    public function evaluate(TestAiEvaluationRequest $evaluationRequest): array
    {
        $apiKey = (string) config('ai_question_generation.cloudflare_workers_ai.api_key');
        $accountId = (string) config('ai_question_generation.cloudflare_workers_ai.account_id');
        $baseUrl = rtrim((string) config('ai_question_generation.cloudflare_workers_ai.base_url'), '/');
        $model = (string) config('ai_question_generation.cloudflare_workers_ai.model');
        $timeout = (int) config('ai_question_generation.cloudflare_workers_ai.timeout_seconds', 180);

        if ($apiKey === '') {
            throw TestAiEvaluationException::providerApiKeyMissing(provider: $this->providerName);
        }

        if ($accountId === '' || $baseUrl === '' || $model === '') {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'configuration',
                reason: 'Cloudflare account_id, base_url, or model is missing.'
            );
        }

        $payload = $this->generateEvaluationPayload(
            evaluationRequest: $evaluationRequest,
            apiKey: $apiKey,
            accountId: $accountId,
            baseUrl: $baseUrl,
            model: $model,
            timeout: $timeout
        );

        $result = $this->normalizer->normalize(
            payload: $payload,
            questionsCount: (int) $evaluationRequest->questions_count,
            provider: $this->providerName
        );

        return [
            'provider' => $this->providerName,
            'model' => $model,
            'result' => $result,
            'raw_response' => $payload,
        ];
    }

    private function generateEvaluationPayload(
        TestAiEvaluationRequest $evaluationRequest,
        string $apiKey,
        string $accountId,
        string $baseUrl,
        string $model,
        int $timeout
    ): array {
        try {
            $response = Http::timeout($timeout)
                ->retry(3, 1000, fn ($exception) => $exception instanceof ConnectionException)
                ->withToken($apiKey)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$baseUrl}/accounts/{$accountId}/ai/v1/chat/completions", [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $this->buildPrompt($evaluationRequest),
                        ],
                    ],
                    'temperature' => (float) config('ai_question_generation.cloudflare_workers_ai.temperature', 0.1),
                    'max_completion_tokens' => (int) config('ai_question_generation.cloudflare_workers_ai.max_tokens', 8192),
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => $this->evaluationSchema(),
                    ],
                    'stream' => false,
                ]);
        } catch (ConnectionException $exception) {
            throw TestAiEvaluationException::providerConnectionFailed(
                provider: $this->providerName,
                operation: 'chat_completions',
                reason: $exception->getMessage()
            );
        } catch (RequestException $exception) {
            $response = $exception->response;

            throw TestAiEvaluationException::providerRequestFailed(
                provider: $this->providerName,
                operation: 'chat_completions',
                status: $response ? (int) $response->status() : 500,
                responseBody: $response?->body() ?? $exception->getMessage()
            );
        }

        if (! $response->successful()) {
            throw TestAiEvaluationException::providerRequestFailed(
                provider: $this->providerName,
                operation: 'chat_completions',
                status: (int) $response->status(),
                responseBody: $response->body()
            );
        }

        $text = $response->json('choices.0.message.content')
            ?? $response->json('choices.0.message.reasoning')
            ?? $response->json('result.response')
            ?? $response->json('response');

        if (! is_string($text) || trim($text) === '') {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'chat_completions',
                reason: 'Cloudflare Workers AI response content is empty.'
            );
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'chat_completions',
                reason: 'Cloudflare Workers AI response is not valid JSON.'
            );
        }

        return $decoded;
    }

    private function buildPrompt(TestAiEvaluationRequest $evaluationRequest): string
    {
        $questionsJson = json_encode(
            $evaluationRequest->input_questions_json,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        return <<<PROMPT
أنت مراجع جودة متخصص في تقييم اختبارات MCQ تعليمية.

المهمة:
- قيّم صحة وجودة الاختبار من 100.
- افحص كل سؤال وخياراته والإجابة المحددة كصحيحة.
- اعتبر السؤال مشبوهاً إذا كان فيه أكثر من إجابة محتملة، أو إجابة صحيحة خاطئة، أو نص غامض، أو خيارات مكررة/متقاربة جداً، أو مشكلة تعليمية واضحة.
- لا تغيّر الأسئلة ولا تقترح صياغة طويلة.
- لا تكتب Markdown أو شرحاً خارج JSON.
- أرجع JSON صالح فقط.

قواعد الخرج:
- score_percentage: رقم صحيح من 0 إلى 100.
- correct_questions: نص بالشكل "40/41".
- suspicious_questions: نص بالشكل "1/41".
- issues: مصفوفة. إذا لا توجد مشاكل أرجع [].
- كل issue يحتوي question_position و problem.
- problem مختصر ولا يزيد عن 100 كلمة.

JSON الاختبار:
{$questionsJson}
PROMPT;
    }

    private function evaluationSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'score_percentage' => [
                    'type' => 'integer',
                    'description' => 'Exam quality score from 0 to 100.',
                ],
                'correct_questions' => [
                    'type' => 'string',
                    'description' => 'Valid questions label, for example 40/41.',
                ],
                'suspicious_questions' => [
                    'type' => 'string',
                    'description' => 'Suspicious questions label, for example 1/41.',
                ],
                'issues' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'question_position' => [
                                'type' => 'integer',
                                'description' => 'The visible question number in the test.',
                            ],
                            'problem' => [
                                'type' => 'string',
                                'description' => 'A short issue summary under 100 words.',
                            ],
                        ],
                        'required' => [
                            'question_position',
                            'problem',
                        ],
                    ],
                ],
            ],
            'required' => [
                'score_percentage',
                'correct_questions',
                'suspicious_questions',
                'issues',
            ],
        ];
    }
}
