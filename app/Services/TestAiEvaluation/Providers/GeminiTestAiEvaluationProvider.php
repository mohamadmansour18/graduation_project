<?php

namespace App\Services\TestAiEvaluation\Providers;

use App\Contracts\TestAiEvaluation\TestAiEvaluationProviderInterface;
use App\Exceptions\Api\TestAiEvaluationException;
use App\Models\TestAiEvaluationRequest;
use App\Services\Admin\TestAiEvaluation\TestAiEvaluationResultNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GeminiTestAiEvaluationProvider implements TestAiEvaluationProviderInterface
{
    private string $providerName = 'Gemini';

    public function __construct(
        private readonly TestAiEvaluationResultNormalizer $normalizer
    ) {}

    public function evaluate(TestAiEvaluationRequest $evaluationRequest): array
    {
        $apiKey = config('ai_question_generation.gemini.api_key');
        $model = config('ai_question_generation.gemini.model');

        if (! $apiKey) {
            throw TestAiEvaluationException::providerApiKeyMissing(provider: $this->providerName);
        }

        $payload = $this->generateEvaluationPayload($evaluationRequest, $apiKey, $model);

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

    private function generateEvaluationPayload(TestAiEvaluationRequest $evaluationRequest, string $apiKey, string $model): array
    {
        $baseUrl = rtrim((string) config('ai_question_generation.gemini.base_url'), '/');
        $timeout = (int) config('ai_question_generation.gemini.timeout_seconds');

        try {
            $response = Http::timeout($timeout)
                ->retry(3, 1000, fn ($exception) => $exception instanceof ConnectionException)
                ->withHeaders([
                    'X-Goog-Api-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$baseUrl}/v1beta/models/{$model}:generateContent", [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => $this->buildPrompt($evaluationRequest),
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $this->evaluationSchema(),
                    ],
                ]);
        } catch (ConnectionException $exception) {
            throw TestAiEvaluationException::providerConnectionFailed(
                provider: $this->providerName,
                operation: 'generateContent',
                reason: $exception->getMessage()
            );
        }

        if (! $response->successful()) {
            throw TestAiEvaluationException::providerRequestFailed(
                provider: $this->providerName,
                operation: 'generateContent',
                status: (int) $response->status(),
                responseBody: $response->body()
            );
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'generateContent',
                reason: 'Gemini response text is empty.'
            );
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'generateContent',
                reason: 'Gemini response is not valid JSON.'
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
- اعتبر السؤال مشبوهاً إذا كان فيه أكثر من إجابة محتملة، أو إجابة صحيحة خاطئة، أو نص غامض، أو مشكلة تعليمية واضحة.
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
