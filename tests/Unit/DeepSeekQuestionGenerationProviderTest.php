<?php

namespace Tests\Unit;

use App\Models\AiQuestionGenerationRequest;
use App\Services\AiQuestionGeneration\AiGeneratedQuestionNormalizer;
use App\Services\AiQuestionGeneration\Extraction\AiQuestionGenerationAssetTextExtractionService;
use App\Services\AiQuestionGeneration\Providers\DeepSeekQuestionGenerationProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeepSeekQuestionGenerationProviderTest extends TestCase
{
    public function test_it_sends_extracted_text_as_plain_message_content(): void
    {
        Config::set('ai_question_generation.deepseek.api_key', 'test-key');
        Config::set('ai_question_generation.deepseek.base_url', 'https://deepseek.test');
        Config::set('ai_question_generation.deepseek.model', 'deepseek-test');
        Config::set('ai_question_generation.deepseek.timeout_seconds', 10);
        Config::set('ai_question_generation.deepseek.temperature', 0.3);
        Config::set('ai_question_generation.deepseek.max_tokens', 8192);
        Config::set('ai_question_generation.deepseek.supported_source_types', ['Images', 'Pdf']);

        Http::fake([
            'https://deepseek.test/chat/completions' => Http::response([
                'choices' => [
                    [
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
                    ],
                ],
            ], 200),
        ]);

        $extractionService = $this->createMock(AiQuestionGenerationAssetTextExtractionService::class);
        $extractionService
            ->expects($this->once())
            ->method('extractPromptContext')
            ->willReturn("File: lesson.pdf\nMime-Type: application/pdf\n\nExtracted lesson text.");

        $provider = new DeepSeekQuestionGenerationProvider(
            normalizer: new AiGeneratedQuestionNormalizer(),
            assetTextExtractionService: $extractionService
        );

        $result = $provider->generate(new AiQuestionGenerationRequest([
            'source_type' => 'Pdf',
            'requested_question_count' => 5,
            'difficulty_level' => 'Easy',
            'language' => 'English',
        ]));

        $this->assertSame('extracted_text', $result['input_mode']);

        Http::assertSent(function ($request): bool {
            $payload = $request->data();
            $content = $payload['messages'][0]['content'] ?? null;

            return $request->url() === 'https://deepseek.test/chat/completions'
                && is_string($content)
                && str_contains($content, 'Extracted lesson text.')
                && ! str_contains($content, 'image_url')
                && ! str_contains($content, 'file_url');
        });
    }
}
