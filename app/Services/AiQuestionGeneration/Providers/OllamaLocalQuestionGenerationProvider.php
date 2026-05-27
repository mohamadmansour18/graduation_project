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

class OllamaLocalQuestionGenerationProvider implements AiQuestionGenerationProviderInterface
{
    private string $providerName = 'OllamaLocal';

    public function __construct(
        private readonly AiGeneratedQuestionNormalizer $normalizer
    ) {}

    /**
     * @throws AiQuestionGenerationException
     */
    public function generate(AiQuestionGenerationRequest $generationRequest): array
    {
        $this->assertSourceTypeIsSupported($generationRequest);

        $baseUrl = rtrim((string) config('ai_question_generation.ollama_local.base_url'), '/');
        $model = (string) config('ai_question_generation.ollama_local.model');
        $timeout = (int) config('ai_question_generation.ollama_local.timeout_seconds', 180);

        if ($baseUrl === '') {
            throw AiQuestionGenerationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'configuration',
                reason: 'OLLAMA_BASE_URL is missing.'
            );
        }

        if ($model === '') {
            throw AiQuestionGenerationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'configuration',
                reason: 'OLLAMA_MODEL is missing.'
            );
        }

        $payload = $this->generateQuestionsPayload(
            generationRequest: $generationRequest,
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
            'ai_question_generation.ollama_local.supported_source_types',
            ['Images']
        );

        if (! is_array($supportedSourceTypes)) {
            $supportedSourceTypes = ['Images'];
        }

        if (! in_array($generationRequest->source_type, $supportedSourceTypes, true)) {
            throw AiQuestionGenerationException::providerUnsupportedSourceType(
                provider: $this->providerName,
                sourceType: $generationRequest->source_type
            );
        }
    }

    private function generateQuestionsPayload(AiQuestionGenerationRequest $generationRequest, string $baseUrl, string $model, int $timeout): array
    {
        $images = $this->buildBase64Images($generationRequest);

        try {
            Log::channel('errors')->info('Ollama local request started.', [
                'generation_request_id' => $generationRequest->id,
                'base_url' => $baseUrl,
                'model' => $model,
                'timeout' => $timeout,
                'images_count' => count($images),
            ]);

            $response = Http::timeout($timeout)
                ->post("{$baseUrl}/api/chat", [
                    'model' => $model,
                    'stream' => false,
                    'format' => [
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
                    ],
                    'keep_alive' => config('ai_question_generation.ollama_local.keep_alive', '720h'),
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $this->buildPrompt($generationRequest),
                            'images' => $images,
                        ],
                    ],
                    'options' => [
                        'temperature' => 0,
                        'num_thread' => (int) config('ai_question_generation.ollama_local.num_thread', 5),
                        'num_ctx' => (int) config('ai_question_generation.ollama_local.num_ctx', 2048),
                        'num_predict' => (int) config('ai_question_generation.ollama_local.num_predict', 900),
                    ],
                ]);

            Log::channel('errors')->info('Ollama local response received.', [
                'generation_request_id' => $generationRequest->id,
                'status' => $response->status(),
                'successful' => $response->successful(),
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
                reason: 'Ollama response message.content is empty.'
            );
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw AiQuestionGenerationException::providerInvalidResponse(
                provider: $this->providerName,
                operation: 'chat',
                reason: 'Ollama response message.content is not valid JSON.'
            );
        }

        return $decoded;
    }

    /**
     * @return array<int, string>
     * @throws AiQuestionGenerationException
     */
    private function buildBase64Images(AiQuestionGenerationRequest $generationRequest): array
    {
        $images = [];

        foreach ($generationRequest->assets as $asset) {
            if (! $this->isImageAsset($asset)) {
                throw AiQuestionGenerationException::providerUnsupportedSourceType(
                    provider: $this->providerName,
                    sourceType: $asset->mime_type
                );
            }

            $filePath = $this->getStoredAssetPath($asset);

            $fileBytes = file_get_contents($filePath);

            if ($fileBytes === false) {
                throw AiQuestionGenerationException::temporaryFileReadFailed(
                    path: $asset->storage_path
                );
            }

            $images[] = base64_encode($fileBytes);
        }

        if ($images === []) {
            throw AiQuestionGenerationException::temporaryFileReadFailed(
                path: null
            );
        }

        return $images;
    }

    private function isImageAsset(AiQuestionGenerationAsset $asset): bool
    {
        return str_starts_with($asset->mime_type, 'image/');
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
أنشئ أسئلة اختيار من متعدد MCQ من الصور التعليمية المرفقة فقط.

القواعد:
- إذا لم تكن الصور تعليمية أو كانت غير واضحة، أرجع content_type = NotEducational و questions = [].
- إذا كانت تعليمية، أرجع content_type = Educational.
- أنشئ {$generationRequest->requested_question_count} أسئلة قدر الإمكان.
- كل سؤال يحتوي 2 إلى 5 خيارات.
- كل سؤال له إجابة صحيحة واحدة فقط.
- لا تخترع معلومات من خارج الصور.
- لا تشرح ولا تكتب Markdown.
- أرجع JSON فقط حسب schema المطلوب.

تعليمات اللغة:
{$languageInstruction}

تعليمات الصعوبة:
{$difficultyInstruction}
PROMPT;

    }
}
