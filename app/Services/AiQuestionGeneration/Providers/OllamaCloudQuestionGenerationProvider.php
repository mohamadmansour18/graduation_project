<?php

namespace App\Services\AiQuestionGeneration\Providers;

use App\Contracts\AiQuestionGeneration\AiQuestionGenerationProviderInterface;
use App\Exceptions\Api\AiQuestionGenerationException;
use App\Models\AiQuestionGenerationRequest;
use App\Services\AiQuestionGeneration\AiGeneratedQuestionNormalizer;
use App\Services\AiQuestionGeneration\Extraction\AiQuestionGenerationAssetTextExtractionService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OllamaCloudQuestionGenerationProvider implements AiQuestionGenerationProviderInterface
{
    private string $providerName = 'OllamaCloud';

    public function __construct(
        private readonly AiGeneratedQuestionNormalizer $normalizer,
        private readonly AiQuestionGenerationAssetTextExtractionService $assetTextExtractionService
    ) {}

    public function generate(AiQuestionGenerationRequest $generationRequest): array
    {
        $this->assertSourceTypeIsSupported($generationRequest);

        $apiKey = (string) config('ai_question_generation.ollama_cloud.api_key');
        $baseUrl = rtrim((string) config('ai_question_generation.ollama_cloud.base_url'), '/');
        $model = (string) config('ai_question_generation.ollama_cloud.model');
        $timeout = (int) config('ai_question_generation.ollama_cloud.timeout_seconds', 180);

        if ($apiKey === '') {
            throw AiQuestionGenerationException::providerApiKeyMissing(
                provider: $this->providerName
            );
        }

        if ($baseUrl === '' || $model === '') {
            throw AiQuestionGenerationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'configuration',
                reason: 'Ollama Cloud base_url or model is missing.'
            );
        }

        $payload = $this->generateQuestionsPayload(
            generationRequest: $generationRequest,
            apiKey: $apiKey,
            baseUrl: $baseUrl,
            model: $model,
            timeout: $timeout
        );

        $questions = $this->normalizer->normalize(
            payload: $payload,
            requestedQuestionCount: $generationRequest->requested_question_count
        );

        return [
            'provider' => $this->providerName,
            'model' => $model,
            'input_mode' => 'extracted_text',
            'questions' => $questions,
        ];
    }

    private function assertSourceTypeIsSupported(AiQuestionGenerationRequest $generationRequest): void
    {
        $supportedSourceTypes = config(
            'ai_question_generation.ollama_cloud.supported_source_types',
            ['Images', 'Pdf']
        );

        if (! is_array($supportedSourceTypes)) {
            $supportedSourceTypes = ['Images', 'Pdf'];
        }

        if (! in_array($generationRequest->source_type, $supportedSourceTypes, true)) {
            throw AiQuestionGenerationException::providerUnsupportedSourceType(
                provider: $this->providerName,
                sourceType: $generationRequest->source_type
            );
        }
    }

    private function generateQuestionsPayload(
        AiQuestionGenerationRequest $generationRequest,
        string $apiKey,
        string $baseUrl,
        string $model,
        int $timeout
    ): array {
        try {
            $response = Http::timeout($timeout)
                ->withToken($apiKey)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$baseUrl}/api/chat", [
                    'model' => $model,
                    'stream' => false,
                    'format' => $this->questionSchema(),
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $this->buildTextOnlyContent($generationRequest),
                        ],
                    ],
                    'options' => [
                        'temperature' => (float) config('ai_question_generation.ollama_cloud.temperature', 0.3),
                        'num_ctx' => (int) config('ai_question_generation.ollama_cloud.num_ctx', 4096),
                        'num_predict' => (int) config('ai_question_generation.ollama_cloud.num_predict', 1200),
                    ],
                ]);
        } catch (ConnectionException $exception) {
            throw AiQuestionGenerationException::providerConnectionFailed(
                provider: $this->providerName,
                operation: 'chat',
                reason: $exception->getMessage()
            );
        }

        if (! $response->successful()) {
            throw AiQuestionGenerationException::providerRequestFailed(
                provider: $this->providerName,
                operation: 'chat',
                status: (int) $response->status(),
                responseBody: $response->body()
            );
        }

        $content = $response->json('message.content');

        if (! is_string($content) || trim($content) === '') {
            throw AiQuestionGenerationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'chat',
                reason: 'Ollama Cloud response message.content is empty.'
            );
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw AiQuestionGenerationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'chat',
                reason: 'Ollama Cloud response message.content is not valid JSON.'
            );
        }

        return $decoded;
    }

    private function buildTextOnlyContent(AiQuestionGenerationRequest $generationRequest): string
    {
        $extractedTextContext = $this->assetTextExtractionService
            ->extractPromptContext($generationRequest);

        return trim(<<<PROMPT
{$this->buildPrompt($generationRequest)}

النص المستخرج من الملفات المرفقة:

{$extractedTextContext}
PROMPT);
    }

    private function buildPrompt(AiQuestionGenerationRequest $generationRequest): string
    {
        $languageInstruction = match ($generationRequest->language) {
            'Arabic' => 'اكتب جميع الأسئلة والخيارات والتلميحات باللغة العربية فقط.',
            'English' => 'Write all questions, options, and hints in English only.',
            'Mixed' => 'اكتب الأسئلة والخيارات بلغة مناسبة للمحتوى، ويمكن المزج بين العربية والإنجليزية عند الحاجة.',
            default => 'Write clear questions.',
        };

        $difficultyInstruction = match ($generationRequest->difficulty_level) {
            'Easy' => 'اجعل مستوى الأسئلة سهلاً ومباشراً.',
            'Medium' => 'اجعل مستوى الأسئلة متوسطاً ويحتاج فهماً للمحتوى.',
            'Hard' => 'اجعل مستوى الأسئلة صعباً نسبياً ويحتاج تحليلاً وربطاً بين المفاهيم.',
            default => 'اجعل مستوى الأسئلة متوسطاً.',
        };

        return <<<PROMPT
أنت مساعد متخصص في إنشاء أسئلة اختيار من متعدد MCQ من نص مستخرج من ملفات تعليمية.

مهم جداً:
- استخدم النص المستخرج من الملفات فقط.
- لا تخترع معلومات من خارج النص.
- لا تكتب Markdown.
- لا تكتب شرحاً خارج JSON.
- أرجع JSON صالح فقط.
- يجب أن يكون الرد كائن JSON واحد فقط.

قبل توليد الأسئلة:
- إذا كان النص غير تعليمي أو فارغاً أو غير واضح، أرجع:
{
  "content_type": "NotEducational",
  "questions": []
}
- إذا كان النص تعليمياً، أرجع:
{
  "content_type": "Educational",
  "questions": [...]
}

المطلوب:
- حاول إنشاء {$generationRequest->requested_question_count} سؤالاً بالضبط.
- كل سؤال يجب أن يحتوي من خيارين إلى خمسة خيارات.
- كل سؤال يجب أن يحتوي إجابة صحيحة واحدة فقط.
- لا تكرر نفس السؤال بصياغة مختلفة.
- لا تستخدم عبارات مثل: "حسب النص" أو "كما ورد في الملف" أو "كما ورد في الصورة".
- اجعل الخيارات الخاطئة معقولة وليست سخيفة.
- اجعل التلميح قصيراً أو null إذا لم يكن ضرورياً.

تعليمات اللغة:
{$languageInstruction}

تعليمات الصعوبة:
{$difficultyInstruction}
PROMPT;
    }

    private function questionSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_type' => [
                    'type' => 'string',
                    'enum' => ['Educational', 'NotEducational'],
                ],
                'questions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'question_text' => ['type' => 'string'],
                            'hint_text' => ['type' => 'string'],
                            'options' => [
                                'type' => 'array',
                                'minItems' => 2,
                                'maxItems' => 5,
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'option_text' => ['type' => 'string'],
                                        'is_correct' => ['type' => 'boolean'],
                                    ],
                                    'required' => ['option_text', 'is_correct'],
                                ],
                            ],
                        ],
                        'required' => ['question_text', 'options'],
                    ],
                ],
            ],
            'required' => ['content_type', 'questions'],
        ];
    }
}
