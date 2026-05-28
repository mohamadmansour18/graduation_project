<?php

namespace Tests\Unit;

use App\Models\AiQuestionGenerationRequest;
use App\Services\AiQuestionGeneration\AiGeneratedQuestionNormalizer;
use App\Services\AiQuestionGeneration\Extraction\AiQuestionGenerationAssetTextExtractionService;
use App\Services\AiQuestionGeneration\Providers\OllamaCloudQuestionGenerationProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaCloudQuestionGenerationProviderTest extends TestCase
{
    public function test_it_sends_extracted_text_to_ollama_cloud_chat_api(): void
    {
        Config::set('ai_question_generation.ollama_cloud.api_key', 'test-key');
        Config::set('ai_question_generation.ollama_cloud.base_url', 'https://ollama.test');
        Config::set('ai_question_generation.ollama_cloud.model', 'gpt-oss:test');
        Config::set('ai_question_generation.ollama_cloud.timeout_seconds', 10);
        Config::set('ai_question_generation.ollama_cloud.temperature', 0.3);
        Config::set('ai_question_generation.ollama_cloud.num_ctx', 4096);
        Config::set('ai_question_generation.ollama_cloud.num_predict', 1200);
        Config::set('ai_question_generation.ollama_cloud.supported_source_types', ['Images', 'Pdf']);

        Http::fake([
            'https://ollama.test/api/chat' => Http::response([
                'message' => [
                    'content' => json_encode([
                        'content_type' => 'Educational',
                        'questions' => [
                            [
                                'question_text' => 'Question?',
                                'hint_text' => null,
                                'options' => [
                                    ['option_text' => 'Correct', 'is_correct' => true],
                                    ['option_text' => 'Wrong', 'is_correct' => false],
                                ],
                            ],
                            [
                                'question_text' => 'Second question?',
                                'hint_text' => null,
                                'options' => [
                                    ['option_text' => 'Correct', 'is_correct' => true],
                                    ['option_text' => 'Wrong', 'is_correct' => false],
                                ],
                            ],
                            [
                                'question_text' => 'Third question?',
                                'hint_text' => null,
                                'options' => [
                                    ['option_text' => 'Correct', 'is_correct' => true],
                                    ['option_text' => 'Wrong', 'is_correct' => false],
                                ],
                            ],
                        ],
                    ]),
                ],
            ], 200),
        ]);

        $extractionService = $this->createMock(AiQuestionGenerationAssetTextExtractionService::class);
        $extractionService
            ->expects($this->once())
            ->method('extractPromptContext')
            ->willReturn("File: lesson.pdf\nMime-Type: application/pdf\n\nExtracted lesson text.");

        $provider = new OllamaCloudQuestionGenerationProvider(
            normalizer: new AiGeneratedQuestionNormalizer(),
            assetTextExtractionService: $extractionService
        );

        $provider->generate(new AiQuestionGenerationRequest([
            'source_type' => 'Pdf',
            'requested_question_count' => 5,
            'difficulty_level' => 'Easy',
            'language' => 'English',
        ]));

        Http::assertSent(function ($request): bool {
            $payload = $request->data();
            $content = $payload['messages'][0]['content'] ?? null;

            return $request->url() === 'https://ollama.test/api/chat'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && ($payload['model'] ?? null) === 'gpt-oss:test'
                && ($payload['stream'] ?? null) === false
                && is_string($content)
                && str_contains($content, 'Extracted lesson text.');
        });
    }
}
