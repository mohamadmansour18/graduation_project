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

class CloudflareWorkersAiQuestionGenerationProvider implements AiQuestionGenerationProviderInterface
{
    private string $providerName = 'CloudflareWorkersAI';

    public function __construct(
        private readonly AiGeneratedQuestionNormalizer $normalizer
    ) {}

    public function generate(AiQuestionGenerationRequest $generationRequest): array
    {
        $this->assertSourceTypeIsSupported($generationRequest);

        $apiKey = (string) config('ai_question_generation.cloudflare_workers_ai.api_key');
        $accountId = (string) config('ai_question_generation.cloudflare_workers_ai.account_id');
        $baseUrl = rtrim((string) config('ai_question_generation.cloudflare_workers_ai.base_url'), '/');
        $model = (string) config('ai_question_generation.cloudflare_workers_ai.model');
        $timeout = (int) config('ai_question_generation.cloudflare_workers_ai.timeout_seconds', 180);

        if ($apiKey === '') {
            throw AiQuestionGenerationException::providerApiKeyMissing(
                provider: $this->providerName
            );
        }

        if ($accountId === '' || $baseUrl === '' || $model === '') {
            throw AiQuestionGenerationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'configuration',
                reason: 'Cloudflare account_id, base_url, or model is missing.'
            );
        }

        $inputMode = $this->inputModeForRequest($generationRequest);

        $payload = $this->generateQuestionsPayload(
            generationRequest: $generationRequest,
            apiKey: $apiKey,
            accountId: $accountId,
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
            'input_mode' => $inputMode,
            'questions' => $questions,
        ];
    }

    private function assertSourceTypeIsSupported(AiQuestionGenerationRequest $generationRequest): void
    {
        $supportedSourceTypes = config(
            'ai_question_generation.cloudflare_workers_ai.supported_source_types',
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
        string $accountId,
        string $baseUrl,
        string $model,
        int $timeout
    ): array {
        $textContext = null;
        $imageDataUrl = null;

        if ($this->shouldSendSingleImageRaw($generationRequest)) {
            $imageDataUrl = $this->buildImageDataUrl($generationRequest->assets->first());
        } else {
            $textContext = $this->convertAssetsToMarkdown(
                generationRequest: $generationRequest,
                apiKey: $apiKey,
                accountId: $accountId,
                baseUrl: $baseUrl,
                timeout: $timeout
            );
        }

        try {
            $response = Http::timeout($timeout)
                ->withToken($apiKey)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$baseUrl}/accounts/{$accountId}/ai/run/{$model}", array_filter([
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $this->buildPrompt($generationRequest, $textContext),
                        ],
                    ],
                    'image' => $imageDataUrl,
                    'temperature' => (float) config('ai_question_generation.cloudflare_workers_ai.temperature', 0.3),
                    'max_tokens' => (int) config('ai_question_generation.cloudflare_workers_ai.max_tokens', 1200),
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'type' => 'object',
                            'properties' => [
                                'content_type' => [
                                    'type' => 'string',
                                ],
                                'questions' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'question_text' => [
                                                'type' => 'string',
                                            ],
                                            'options' => [
                                                'type' => 'array',
                                                'items' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'option_text' => [
                                                            'type' => 'string',
                                                        ],
                                                        'is_correct' => [
                                                            'type' => 'boolean',
                                                        ],
                                                    ],
                                                    'required' => [
                                                        'option_text',
                                                        'is_correct',
                                                    ],
                                                ],
                                            ],
                                        ],
                                        'required' => [
                                            'question_text',
                                            'options',
                                        ],
                                    ],
                                ],
                            ],
                            'required' => [
                                'content_type',
                                'questions',
                            ],
                        ],
                    ],
                    'stream' => false,
                ], fn ($value): bool => $value !== null));
        } catch (ConnectionException $exception) {
            throw AiQuestionGenerationException::providerConnectionFailed(
                provider: $this->providerName,
                operation: 'run',
                reason: $exception->getMessage()
            );
        }

        if (! $response->successful()) {
            throw AiQuestionGenerationException::providerRequestFailed(
                provider: $this->providerName,
                operation: 'run',
                status: (int) $response->status(),
                responseBody: $response->body()
            );
        }

        $text = $response->json('result.response') ?? $response->json('response');

        if (! is_string($text) || trim($text) === '') {
            throw AiQuestionGenerationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'run',
                reason: 'Cloudflare Workers AI response result.response is empty.'
            );
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw AiQuestionGenerationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'run',
                reason: 'Cloudflare Workers AI response is not valid JSON.'
            );
        }

        return $decoded;
    }

    private function convertAssetsToMarkdown(
        AiQuestionGenerationRequest $generationRequest,
        string $apiKey,
        string $accountId,
        string $baseUrl,
        int $timeout
    ): string {
        try {
            $request = Http::timeout($timeout)
                ->withToken($apiKey)
                ->acceptJson();

            foreach ($generationRequest->assets->sortBy('position')->values() as $asset) {
                $fileBytes = file_get_contents($this->getStoredAssetPath($asset));

                if ($fileBytes === false) {
                    throw AiQuestionGenerationException::temporaryFileReadFailed(
                        path: $asset->storage_path
                    );
                }

                $request = $request->attach(
                    'files',
                    $fileBytes,
                    $asset->original_name
                );
            }

            $response = $request->post("{$baseUrl}/accounts/{$accountId}/ai/tomarkdown");

        } catch (ConnectionException $exception) {
            throw AiQuestionGenerationException::providerConnectionFailed(
                provider: $this->providerName,
                operation: 'to_markdown',
                reason: $exception->getMessage()
            );
        }

        if (! $response->successful()) {
            throw AiQuestionGenerationException::providerRequestFailed(
                provider: $this->providerName,
                operation: 'to_markdown',
                status: (int) $response->status(),
                responseBody: $response->body()
            );
        }

        $results = $response->json('result');

        if (! is_array($results)) {
            throw AiQuestionGenerationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'to_markdown',
                reason: 'Cloudflare toMarkdown result is not an array.'
            );
        }

        $sections = [];

        foreach ($results as $result) {
            if (($result['format'] ?? null) === 'error') {
                throw AiQuestionGenerationException::providerInvalidResponse(
                    provider: $this->providerName,
                    operation: 'to_markdown',
                    reason: (string) ($result['error'] ?? 'Cloudflare toMarkdown failed for one file.')
                );
            }

            $data = $result['data'] ?? null;

            if (! is_string($data) || trim($data) === '') {
                continue;
            }

            $name = (string) ($result['name'] ?? 'uploaded-file');
            $mimeType = (string) ($result['mimeType'] ?? 'application/octet-stream');

            $sections[] = trim(<<<TEXT
File: {$name}
Mime-Type: {$mimeType}

{$data}
TEXT);
        }

        $markdown = trim(implode("\n\n---\n\n", $sections));

        if ($markdown === '') {
            throw AiQuestionGenerationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'to_markdown',
                reason: 'Cloudflare toMarkdown returned empty markdown.'
            );
        }

        return $markdown;
    }

    private function shouldSendSingleImageRaw(AiQuestionGenerationRequest $generationRequest): bool
    {
        return $generationRequest->source_type === 'Images'
            && $generationRequest->assets->count() === 1
            && $this->isImageAsset($generationRequest->assets->first());
    }

    private function inputModeForRequest(AiQuestionGenerationRequest $generationRequest): string
    {
        return $this->shouldSendSingleImageRaw($generationRequest)
            ? 'raw_image'
            : 'toMarkdown';
    }

    private function buildImageDataUrl(AiQuestionGenerationAsset $asset): string
    {
        $fileBytes = file_get_contents($this->getStoredAssetPath($asset));

        if ($fileBytes === false) {
            throw AiQuestionGenerationException::temporaryFileReadFailed(
                path: $asset->storage_path
            );
        }

        return 'data:' . $asset->mime_type . ';base64,' . base64_encode($fileBytes);
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

    private function isImageAsset(?AiQuestionGenerationAsset $asset): bool
    {
        return $asset !== null && str_starts_with((string) $asset->mime_type, 'image/');
    }

    private function buildPrompt(AiQuestionGenerationRequest $generationRequest, ?string $textContext): string
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

        $sourceInstruction = $textContext === null
            ? 'استخدم الصورة المرفقة فقط.'
            : "استخدم النص/Markdown المستخرج من الملفات فقط:\n\n{$textContext}";

        return <<<PROMPT
أنت مساعد متخصص في إنشاء أسئلة اختيار من متعدد MCQ من محتوى تعليمي.

مهم جداً:
- {$sourceInstruction}
- لا تخترع معلومات من خارج المحتوى.
- لا تكتب Markdown.
- لا تكتب شرحاً خارج JSON.
- أرجع JSON صالح فقط.
- يجب أن يكون الرد كائن JSON واحد فقط.

قبل توليد الأسئلة:
- إذا كان المحتوى غير تعليمي أو فارغاً أو غير واضح، أرجع:
{
  "content_type": "NotEducational",
  "questions": []
}
- إذا كان المحتوى تعليمياً، أرجع:
{
  "content_type": "Educational",
  "questions": [...]
}

المطلوب:
- حاول إنشاء {$generationRequest->requested_question_count} سؤالاً بالضبط.
- كل سؤال يجب أن يحتوي من خيارين إلى ثلاثة خيارات.
- كل سؤال يجب أن يحتوي إجابة صحيحة واحدة فقط.
- لا تكرر نفس السؤال بصياغة مختلفة.
- لا تستخدم عبارات مثل: "حسب النص" أو "كما ورد في الملف" أو "كما ورد في الصورة" أو "الظاهر في الصورة".
- اجعل الخيارات الخاطئة معقولة وليست سخيفة.

تعليمات اللغة:
{$languageInstruction}

تعليمات الصعوبة:
{$difficultyInstruction}

PROMPT;
    }
}
