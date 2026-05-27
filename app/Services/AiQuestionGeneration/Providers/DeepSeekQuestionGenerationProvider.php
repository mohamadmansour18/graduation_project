<?php

namespace App\Services\AiQuestionGeneration\Providers;

use App\Contracts\AiQuestionGeneration\AiQuestionGenerationProviderInterface;
use App\Exceptions\Api\AiQuestionGenerationException;
use App\Models\AiQuestionGenerationAsset;
use App\Models\AiQuestionGenerationRequest;
use App\Services\AiQuestionGeneration\AiGeneratedQuestionNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DeepSeekQuestionGenerationProvider implements AiQuestionGenerationProviderInterface
{
    private string $providerName = 'DeepSeek';

    public function __construct(
        private readonly AiGeneratedQuestionNormalizer $normalizer
    ) {}

    public function generate(AiQuestionGenerationRequest $generationRequest): array
    {
        $this->assertSourceTypeIsSupported($generationRequest);

        $apiKey = (string) config('ai_question_generation.deepseek.api_key');
        $baseUrl = rtrim((string) config('ai_question_generation.deepseek.base_url'), '/');
        $model = (string) config('ai_question_generation.deepseek.model');
        $timeout = (int) config('ai_question_generation.deepseek.timeout_seconds', 160);

        if ($apiKey === '') {
            throw AiQuestionGenerationException::providerApiKeyMissing(
                provider: $this->providerName
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
            'questions' => $questions,
        ];
    }

    private function assertSourceTypeIsSupported(AiQuestionGenerationRequest $generationRequest): void
    {
        $supportedSourceTypes = config(
            'ai_question_generation.deepseek.supported_source_types',
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
        $content = [];

        $content[] = [
            'type' => 'text',
            'text' => $this->buildPrompt($generationRequest),
        ];

        foreach ($generationRequest->assets as $asset) {
            $content[] = $this->buildAssetContentPart($asset);
        }

        try {
            $response = Http::timeout($timeout)
                ->withToken($apiKey)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $content,
                        ],
                    ],
                    'temperature' => (float) config('ai_question_generation.deepseek.temperature', 0.3),
                    'max_tokens' => (int) config('ai_question_generation.deepseek.max_tokens', 8192),
                    'response_format' => [
                        'type' => 'json_object',
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
                reason: 'DeepSeek response choices.0.message.content is empty.'
            );
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw AiQuestionGenerationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'chat_completions',
                reason: 'DeepSeek response is not valid JSON.'
            );
        }

        return $decoded;
    }

    private function buildAssetContentPart(AiQuestionGenerationAsset $asset): array
    {
        $filePath = $this->getStoredAssetPath($asset);

        $fileBytes = file_get_contents($filePath);

        if ($fileBytes === false) {
            throw AiQuestionGenerationException::temporaryFileReadFailed(
                path: $asset->storage_path
            );
        }

        $dataUrl = 'data:' . $asset->mime_type . ';base64,' . base64_encode($fileBytes);

        if ($this->isImageAsset($asset)) {
            return [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $dataUrl,
                ],
            ];
        }

        if ($this->isPdfAsset($asset)) {
            return [
                'type' => 'file_url',
                'file_url' => [
                    'url' => $dataUrl,
                    'filename' => $asset->original_name,
                ],
            ];
        }

        throw AiQuestionGenerationException::providerUnsupportedSourceType(
            provider: $this->providerName,
            sourceType: $asset->mime_type
        );
    }

    private function getStoredAssetPath(AiQuestionGenerationAsset $asset): string
    {
        $filePath = Storage::disk($asset->storage_disk)->path($asset->storage_path);

        if (! is_file($filePath)) {
            throw AiQuestionGenerationException::temporaryFileMissing(
                path: $asset->storage_path
            );
        }

        return $filePath;
    }

    private function isImageAsset(AiQuestionGenerationAsset $asset): bool
    {
        return str_starts_with($asset->mime_type, 'image/');
    }

    private function isPdfAsset(AiQuestionGenerationAsset $asset): bool
    {
        return $asset->mime_type === 'application/pdf';
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
أنت مساعد متخصص في إنشاء أسئلة اختيار من متعدد MCQ من الملفات أو الصور التعليمية المرفقة.

مهم جداً:
- اقرأ محتوى الملفات أو الصور المرفقة فقط.
- لا تخترع معلومات من خارج المحتوى المرفق.
- لا تكتب Markdown.
- لا تكتب أي شرح خارج JSON.
- أرجع JSON صالح فقط.
- يجب أن تحتوي الاستجابة على كائن JSON واحد فقط.

قبل توليد الأسئلة:
- قيّم هل المحتوى المرفق يحتوي على مادة علمية أو تعليمية مناسبة لتوليد أسئلة منه.
- إذا كان المحتوى صورة شخصية، عشوائياً، غير واضح، فارغاً، أو غير تعليمي، أرجع:
{
  "content_type": "NotEducational",
  "questions": []
}
- إذا كان المحتوى تعليمياً أو علمياً، أرجع:
{
  "content_type": "Educational",
  "questions": [...]
}

المطلوب:
- حاول إنشاء {$generationRequest->requested_question_count} سؤالاً بالضبط.
- كل سؤال يجب أن يحتوي من خيارين إلى خمسة خيارات.
- كل سؤال يجب أن يحتوي إجابة صحيحة واحدة فقط.
- لا تكرر نفس السؤال بصياغة مختلفة.
- لا تستخدم عبارات مثل: "حسب النص" أو "كما ورد في الملف" أو "كما ورد في الصورة" داخل نص السؤال.
- لا تستخدم أي معلومة غير موجودة في المحتوى المرفق.
- اجعل الخيارات الخاطئة معقولة وليست سخيفة.
- اجعل التلميح قصيراً أو null إذا لم يكن ضرورياً.

تعليمات اللغة:
{$languageInstruction}

تعليمات الصعوبة:
{$difficultyInstruction}

صيغة JSON المطلوبة حرفياً:
{
  "content_type": "Educational",
  "questions": [
    {
      "question_text": "نص السؤال",
      "hint_text": "تلميح قصير أو null",
      "options": [
        {
          "option_text": "نص الخيار",
          "is_correct": true
        },
        {
          "option_text": "نص الخيار",
          "is_correct": false
        }
      ]
    }
  ]
}
PROMPT;
    }
}
