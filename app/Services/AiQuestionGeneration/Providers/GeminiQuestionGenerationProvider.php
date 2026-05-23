<?php

namespace App\Services\AiQuestionGeneration\Providers;

use App\Contracts\AiQuestionGeneration\AiQuestionGenerationProviderInterface;
use App\Exceptions\Api\AiQuestionGenerationException;
use App\Models\AiQuestionGenerationAsset;
use App\Models\AiQuestionGenerationRequest;
use App\Services\AiQuestionGeneration\AiGeneratedQuestionNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GeminiQuestionGenerationProvider implements AiQuestionGenerationProviderInterface
{
    private const int|float MAX_INLINE_IMAGE_BYTES = 14 * 1024 * 1024; //14 MB : Byte -> KB -> MB

    private string $providerName = 'Gemini';

    public function __construct(
        private readonly AiGeneratedQuestionNormalizer $normalizer
    ) {}

    public function generate(AiQuestionGenerationRequest $generationRequest): array
    {
        $apiKey = config('ai_question_generation.gemini.api_key');
        $model = config('ai_question_generation.gemini.model');

        if (! $apiKey) {
            throw AiQuestionGenerationException::providerApiKeyMissing();
        }

        $contentParts = [];

        $shouldSendImagesInline = $this->shouldSendImagesInline($generationRequest);

        foreach ($generationRequest->assets as $asset) {

            if ($shouldSendImagesInline && $this->isImageAsset($asset)) {
                $contentParts[] = $this->buildInlineDataPart($asset);

                continue;
            }

            $uploadedFile = $this->uploadAssetToGemini($asset, $apiKey);

            $contentParts[] = [
                'file_data' => [
                    'mime_type' => $uploadedFile['mime_type'],
                    'file_uri' => $uploadedFile['uri'],
                ],
            ];
        }

        $payload = $this->generateQuestionsPayload(
            generationRequest: $generationRequest,
            contentParts: $contentParts,
            apiKey: $apiKey,
            model: $model
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

    private function uploadAssetToGemini(AiQuestionGenerationAsset $asset, string $apiKey): array
    {
        $filePath = $this->getStoredAssetPath($asset);

        $baseUrl = rtrim(config('ai_question_generation.gemini.base_url'), '/');
        $timeout = (int) config('ai_question_generation.gemini.timeout_seconds');

        $startUploadResponse = Http::timeout($timeout)
            ->retry(3, 1000, fn ($exception) => $exception instanceof ConnectionException)
            ->withHeaders([
                'X-Goog-Upload-Protocol' => 'resumable',
                'X-Goog-Upload-Command' => 'start',
                'X-Goog-Upload-Header-Content-Length' => (string) $asset->size_bytes,
                'X-Goog-Upload-Header-Content-Type' => $asset->mime_type,
                'Content-Type' => 'application/json',
            ])
            ->post($this->buildGeminiApiUrl("{$baseUrl}/upload/v1beta/files", $apiKey), [
                'file' => [
                    'display_name' => $asset->original_name,
                ],
            ]);

        if (! $startUploadResponse->successful()) {
            throw new RuntimeException(
                'Failed to start Gemini file upload.');
        }

        $uploadUrl = $startUploadResponse->header('X-Goog-Upload-URL');

        if (! $uploadUrl) {
            throw new RuntimeException('Gemini upload URL is missing.');
        }

        $fileBytes = file_get_contents($filePath);

        if ($fileBytes === false) {
            throw new RuntimeException('Failed to read temporary file');
        }

        $uploadResponse = Http::timeout($timeout)
            ->retry(3, 1000, fn ($exception) => $exception instanceof ConnectionException)
            ->withBody($fileBytes, $asset->mime_type)
            ->withHeaders([
                'Content-Length' => (string) $asset->size_bytes,
                'X-Goog-Upload-Offset' => '0',
                'X-Goog-Upload-Command' => 'upload, finalize',
            ])
            ->post($uploadUrl);

        if (! $uploadResponse->successful()) {
            throw new RuntimeException('Failed to upload Gemini file bytes.');
        }

        $file = $uploadResponse->json('file');

        if (! is_array($file) || empty($file['uri']) || empty($file['mimeType'])) {
            throw new RuntimeException('Invalid Gemini uploaded file response');
        }

        return [
            'uri' => $file['uri'], //uri related of file (not in local storage put in gemini after upload file on it)
            'mime_type' => $file['mimeType'],
        ];
    }

    private function buildInlineDataPart(AiQuestionGenerationAsset $asset): array
    {
        $filePath = $this->getStoredAssetPath($asset);
        $fileBytes = file_get_contents($filePath);

        if ($fileBytes === false) {
            throw new RuntimeException('Failed to read temporary file');
        }

        return [
            'inline_data' => [
                'mime_type' => $asset->mime_type,
                'data' => base64_encode($fileBytes),
            ],
        ];
    }

    private function shouldSendImagesInline(AiQuestionGenerationRequest $generationRequest): bool
    {
        if ($generationRequest->source_type !== 'Images') {
            return false;
        }

        $totalBytes = $generationRequest->assets
            ->filter(fn (AiQuestionGenerationAsset $asset) => $this->isImageAsset($asset))
            ->sum('size_bytes');

        return $totalBytes <= self::MAX_INLINE_IMAGE_BYTES;
    }

    private function isImageAsset(AiQuestionGenerationAsset $asset): bool
    {
        return str_starts_with($asset->mime_type, 'image/');
    }

    private function getStoredAssetPath(AiQuestionGenerationAsset $asset): string
    {
        $filePath = Storage::disk($asset->storage_disk)->path($asset->storage_path);

        if (! is_file($filePath)) {
            throw new RuntimeException('Temporary file does not exist');
        }

        return $filePath;
    }

    private function generateQuestionsPayload(AiQuestionGenerationRequest $generationRequest, array $contentParts, string $apiKey, string $model): array
    {
        $baseUrl = rtrim(config('ai_question_generation.gemini.base_url'), '/');
        $timeout = (int) config('ai_question_generation.gemini.timeout_seconds');

        $parts = $contentParts;
        $parts[] = [
            'text' => $this->buildPrompt($generationRequest),
        ];


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
                        'parts' => $parts,
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $this->questionSchema(),
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini generateContent request failed.');
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('Gemini response text is empty.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini response is not valid JSON.');
        }

        return $decoded;
    }

    private function buildGeminiApiUrl(string $url, string $apiKey): string
    {
        return $url . '?' . http_build_query(
            ['key' => $apiKey],
            '',
            '&',
            PHP_QUERY_RFC3986
        );
    }

    private function buildPrompt(AiQuestionGenerationRequest $generationRequest): string
    {
        $languageInstruction = match ($generationRequest->language) {
            'Arabic' => 'اكتب جميع الأسئلة والخيارات والتلميحات باللغة العربية فقط',
            'English' => 'Write all questions, options, and hints in English only.',
            default => 'Write clear questions.',
        };

        $difficultyInstruction = match ($generationRequest->difficulty_level) {
            'Easy' => 'اجعل مستوى الأسئلة سهلاً ومباشراً.',
            'Medium' => 'اجعل مستوى الأسئلة متوسطاً ويحتاج فهماً للمحتوى.',
            'Hard' => 'اجعل مستوى الأسئلة صعباً نسبياً ويحتاج تحليلاً وربطاً بين المفاهيم.',
            default => 'اجعل مستوى الأسئلة متوسطاً.',
        };

        return <<<PROMPT
أنت مساعد متخصص في إنشاء أسئلة اختيار من متعدد MCQ من محتوى تعليمي.

المطلوب:
- أنشئ {$generationRequest->requested_question_count} سؤالاً بالضبط.
- كل سؤال يجب أن يحتوي من خيارين إلى خمسة خيارات.
- كل سؤال يجب أن يحتوي إجابة صحيحة واحدة فقط.
- لا تكرر نفس السؤال بصياغة مختلفة.
- لا تستخدم عبارات مثل: "حسب النص" أو "كما ورد في الملف" داخل نص السؤال.
- لا تخترع معلومات خارج الملفات المرفقة.
- إذا كان المحتوى غير كافٍ لإنشاء أسئلة جيدة، أرجع أسئلة أقل فقط عند الضرورة القصوى، لكن حاول الالتزام بالعدد المطلوب.
- اجعل الخيارات الخاطئة معقولة وليست سخيفة.
- اجعل التلميح قصيراً أو null إذا لم يكن ضرورياً.

تعليمات اللغة:
{$languageInstruction}

تعليمات الصعوبة:
{$difficultyInstruction}

أرجع النتيجة بصيغة JSON فقط حسب schema المطلوب.
PROMPT;

    }

    private function questionSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'content_type' => [
                    'type' => 'string',
                    'enum' => ['Educational', 'NotEducational', 'Unclear'],
                    'description' => 'Classify whether the uploaded content is educational enough to generate MCQ questions.',
                ],
                'questions' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'question_text' => [
                                'type' => 'string',
                                'description' => 'The MCQ question text.',
                            ],
                            'hint_text' => [
                                'type' => 'string',
                                'description' => 'A short optional hint. Return an empty string if no hint is needed.',
                            ],
                            'options' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'option_text' => [
                                            'type' => 'string',
                                            'description' => 'The option text.',
                                        ],
                                        'is_correct' => [
                                            'type' => 'boolean',
                                            'description' => 'True only for the correct option.',
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
                            'hint_text',
                            'options',
                        ],
                    ],
                ],
            ],
            'required' => [
                'content_type',
                'questions',
            ],
        ];
    }
}
