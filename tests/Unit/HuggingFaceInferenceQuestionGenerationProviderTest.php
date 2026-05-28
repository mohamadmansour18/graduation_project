<?php

namespace Tests\Unit;

use App\Models\AiQuestionGenerationRequest;
use App\Services\AiQuestionGeneration\AiGeneratedQuestionNormalizer;
use App\Services\AiQuestionGeneration\Extraction\AiQuestionGenerationAssetTextExtractionService;
use App\Services\AiQuestionGeneration\Providers\HuggingFaceInferenceQuestionGenerationProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HuggingFaceInferenceQuestionGenerationProviderTest extends TestCase
{
    public function test_it_sends_extracted_text_to_huggingface_chat_completions_api(): void
    {
        Config::set('ai_question_generation.huggingface.api_key', 'hf-test-key');
        Config::set('ai_question_generation.huggingface.base_url', 'https://hf.test/v1');
        Config::set('ai_question_generation.huggingface.model', 'openai/gpt-oss-20b:cheapest');
        Config::set('ai_question_generation.huggingface.timeout_seconds', 10);
        Config::set('ai_question_generation.huggingface.temperature', 0.3);
        Config::set('ai_question_generation.huggingface.max_tokens', 8192);
        Config::set('ai_question_generation.huggingface.bill_to', 'nerd-org');
        Config::set('ai_question_generation.huggingface.supported_source_types', ['Images', 'Pdf']);

        Http::fake([
            'https://hf.test/v1/chat/completions' => Http::response([
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

        $provider = new HuggingFaceInferenceQuestionGenerationProvider(
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

            return $request->url() === 'https://hf.test/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer hf-test-key')
                && $request->hasHeader('X-HF-Bill-To', 'nerd-org')
                && ($payload['model'] ?? null) === 'openai/gpt-oss-20b:cheapest'
                && ($payload['response_format']['type'] ?? null) === 'json_object'
                && is_string($content)
                && str_contains($content, 'Extracted lesson text.');
        });
    }
}
