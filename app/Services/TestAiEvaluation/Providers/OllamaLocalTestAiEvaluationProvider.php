<?php

namespace App\Services\TestAiEvaluation\Providers;

use App\Contracts\TestAiEvaluation\TestAiEvaluationProviderInterface;
use App\Exceptions\Api\TestAiEvaluationException;
use App\Models\TestAiEvaluationRequest;
use App\Services\Admin\TestAiEvaluation\TestAiEvaluationResultNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaLocalTestAiEvaluationProvider implements TestAiEvaluationProviderInterface
{
    private string $providerName = 'OllamaLocal';

    public function __construct(
        private readonly TestAiEvaluationResultNormalizer $normalizer
    ) {}

    public function evaluate(TestAiEvaluationRequest $evaluationRequest): array
    {
        $baseUrl = rtrim((string) config('ai_question_generation.ollama_local.base_url'), '/');
        $model = (string) config('ai_question_generation.ollama_local.model');
        $timeout = (int) config('ai_question_generation.ollama_local.timeout_seconds', 180);

        if ($baseUrl === '') {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'configuration',
                reason: 'OLLAMA_BASE_URL is missing.'
            );
        }

        if ($model === '') {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'configuration',
                reason: 'OLLAMA_MODEL is missing.'
            );
        }

        $payload = $this->generateEvaluationPayload(
            evaluationRequest: $evaluationRequest,
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

    private function generateEvaluationPayload(TestAiEvaluationRequest $evaluationRequest, string $baseUrl, string $model, int $timeout): array
    {
        try {
            Log::channel('errors')->info('Ollama local test evaluation request started.', [
                'evaluation_request_id' => $evaluationRequest->id,
                'base_url' => $baseUrl,
                'model' => $model,
                'timeout' => $timeout,
            ]);

            $response = Http::timeout($timeout)
                ->post("{$baseUrl}/api/chat", [
                    'model' => $model,
                    'stream' => false,
                    'format' => $this->evaluationSchema(),
                    'keep_alive' => config('ai_question_generation.ollama_local.keep_alive', '720h'),
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $this->buildPrompt($evaluationRequest),
                        ],
                    ],
                    'options' => [
                        'temperature' => 0,
                        'num_thread' => (int) config('ai_question_generation.ollama_local.num_thread', 5),
                        'num_ctx' => (int) config('ai_question_generation.ollama_local.num_ctx', 2048),
                        'num_predict' => (int) config('ai_question_generation.ollama_local.num_predict', 900),
                    ],
                ]);
        } catch (ConnectionException $exception) {
            throw TestAiEvaluationException::providerConnectionFailed(
                provider: $this->providerName,
                operation: 'chat',
                reason: $exception->getMessage()
            );
        } catch (RequestException $exception) {
            $response = $exception->response;

            throw TestAiEvaluationException::providerRequestFailed(
                provider: $this->providerName,
                operation: 'chat',
                status: $response ? (int) $response->status() : 500,
                responseBody: $response?->body() ?? $exception->getMessage()
            );
        }

        if (! $response->successful()) {
            throw TestAiEvaluationException::providerRequestFailed(
                provider: $this->providerName,
                operation: 'chat',
                status: (int) $response->status(),
                responseBody: $response->body()
            );
        }

        $content = $response->json('message.content');

        if (! is_string($content) || trim($content) === '') {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'chat',
                reason: 'Ollama response message.content is empty.'
            );
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw TestAiEvaluationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'chat',
                reason: 'Ollama response message.content is not valid JSON.'
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
                'score_percentage' => ['type' => 'integer'],
                'correct_questions' => ['type' => 'string'],
                'suspicious_questions' => ['type' => 'string'],
                'issues' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
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
