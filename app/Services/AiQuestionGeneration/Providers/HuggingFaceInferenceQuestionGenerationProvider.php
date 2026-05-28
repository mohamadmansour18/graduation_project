<?php

namespace App\Services\AiQuestionGeneration\Providers;

use App\Contracts\AiQuestionGeneration\AiQuestionGenerationProviderInterface;
use App\Exceptions\Api\AiQuestionGenerationException;
use App\Models\AiQuestionGenerationRequest;
use App\Services\AiQuestionGeneration\AiGeneratedQuestionNormalizer;
use App\Services\AiQuestionGeneration\Extraction\AiQuestionGenerationAssetTextExtractionService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HuggingFaceInferenceQuestionGenerationProvider implements AiQuestionGenerationProviderInterface
{
    private string $providerName = 'HuggingFace';

    public function __construct(
        private readonly AiGeneratedQuestionNormalizer $normalizer,
        private readonly AiQuestionGenerationAssetTextExtractionService $assetTextExtractionService
    ) {}

    public function generate(AiQuestionGenerationRequest $generationRequest): array
    {
        $this->assertSourceTypeIsSupported($generationRequest);

        $apiKey = (string) config('ai_question_generation.huggingface.api_key');
        $baseUrl = rtrim((string) config('ai_question_generation.huggingface.base_url'), '/');
        $model = (string) config('ai_question_generation.huggingface.model');
        $timeout = (int) config('ai_question_generation.huggingface.timeout_seconds', 180);

        if ($apiKey === '') {
            throw AiQuestionGenerationException::providerApiKeyMissing(
                provider: $this->providerName
            );
        }

        if ($baseUrl === '' || $model === '') {
            throw AiQuestionGenerationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'configuration',
                reason: 'Hugging Face base_url or model is missing.'
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
            'ai_question_generation.huggingface.supported_source_types',
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
                ->withToken($apiKey)
                ->withHeaders($headers)
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $this->buildTextOnlyContent($generationRequest),
                        ],
                    ],
                    'temperature' => (float) config('ai_question_generation.huggingface.temperature', 0.3),
                    'max_tokens' => (int) config('ai_question_generation.huggingface.max_tokens', 8192),
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'ai_question_generation_response',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'properties' => [
                                    'content_type' => [
                                        'type' => 'string',
                                        'enum' => ['Educational', 'NotEducational'],
                                    ],
                                    'questions' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'object',
                                            'additionalProperties' => false,
                                            'properties' => [
                                                'question_text' => ['type' => 'string'],
                                                'options' => [
                                                    'type' => 'array',
                                                    'minItems' => 2,
                                                    'maxItems' => 3,
                                                    'items' => [
                                                        'type' => 'object',
                                                        'additionalProperties' => false,
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
                            ],
                        ],
                    ],
                    'stream' => false,
                ]);
        } catch (ConnectionException $exception) {
            throw AiQuestionGenerationException::providerConnectionFailed(
                provider: $this->providerName,
                operation: 'chat_completions',
                reason: $exception->getMessage()
            );
        }

        if (! $response->successful()) {
            throw AiQuestionGenerationException::providerRequestFailed(
                provider: $this->providerName,
                operation: 'chat_completions',
                status: (int) $response->status(),
                responseBody: $response->body()
            );
        }

        $text = $response->json('choices.0.message.content');

        if (! is_string($text) || trim($text) === '') {
            throw AiQuestionGenerationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'chat_completions',
                reason: 'Hugging Face response choices.0.message.content is empty.'
            );
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw AiQuestionGenerationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'chat_completions',
                reason: 'Hugging Face response is not valid JSON.'
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
- كل سؤال يجب أن يحتوي من خيارين إلى ثلاثة خيارات.
- كل سؤال يجب أن يحتوي إجابة صحيحة واحدة فقط.
- لا تكرر نفس السؤال بصياغة مختلفة.
- لا تستخدم عبارات مثل: "كما ورد في النص" أو "كما ورد في الملف" أو "كما ورد في الصورة" أو "المذكورة في النص".
- اجعل الخيارات الخاطئة معقولة وليست سخيفة.

تعليمات اللغة:
{$languageInstruction}

تعليمات الصعوبة:
{$difficultyInstruction}
PROMPT;
    }
}
