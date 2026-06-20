<?php

namespace App\Services\TestAiEvaluation\Providers;

use App\Contracts\TestAiEvaluation\TestAiEvaluationProviderInterface;
use App\Exceptions\Api\TestAiEvaluationException;
use App\Models\TestAiEvaluationRequest;
use App\Services\Admin\TestAiEvaluation\TestAiEvaluationResultNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class HuggingFaceTestAiEvaluationProvider implements TestAiEvaluationProviderInterface
{
    private string $providerName = 'HuggingFace';

    public function __construct(
        private readonly TestAiEvaluationResultNormalizer $normalizer
    ) {}

    public function evaluate(TestAiEvaluationRequest $evaluationRequest): array
    {
        $apiKey = (string) config('ai_question_generation.huggingface.api_key');
        $baseUrl = rtrim((string) config('ai_question_generation.huggingface.base_url'), '/');
        $model = (string) config('ai_question_generation.huggingface.model');
        $timeout = (int) config('ai_question_generation.huggingface.timeout_seconds', 180);

        if ($apiKey === '') {
            throw TestAiEvaluationException::providerApiKeyMissing(provider: $this->providerName);
        }

        if ($baseUrl === '' || $model === '') {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'configuration',
                reason: 'Hugging Face base_url or model is missing.'
            );
        }

        $payload = $this->generateEvaluationPayload(
            evaluationRequest: $evaluationRequest,
            apiKey: $apiKey,
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

    private function generateEvaluationPayload(TestAiEvaluationRequest $evaluationRequest, string $apiKey, string $baseUrl, string $model, int $timeout): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $billTo = trim((string) config('ai_question_generation.huggingface.bill_to', ''));

        if ($billTo !== '') {
            $headers['X-HF-Bill-To'] = $billTo;
        }

        try {
            $response = Http::timeout($timeout)
                ->retry(3, 1000, fn ($exception) => $exception instanceof ConnectionException)
                ->withToken($apiKey)
                ->withHeaders($headers)
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $this->buildPrompt($evaluationRequest),
                        ],
                    ],
                    'temperature' => (float) config('ai_question_generation.huggingface.temperature', 0.3),
                    'max_tokens' => (int) config('ai_question_generation.huggingface.max_tokens', 8192),
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'test_ai_evaluation_response',
                            'strict' => true,
                            'schema' => $this->evaluationSchema(),
                        ],
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

        $text = $response->json('choices.0.message.content');

        if (! is_string($text) || trim($text) === '') {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'chat_completions',
                reason: 'Hugging Face response choices.0.message.content is empty.'
            );
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'chat_completions',
                reason: 'Hugging Face response is not valid JSON.'
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
            'additionalProperties' => false,
            'properties' => [
                'score_percentage' => ['type' => 'integer'],
                'correct_questions' => ['type' => 'string'],
                'suspicious_questions' => ['type' => 'string'],
                'issues' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'question_position' => ['type' => 'integer'],
                            'problem' => ['type' => 'string'],
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
