<?php

namespace Tests\Unit;

use App\Models\AiQuestionGenerationAsset;
use App\Models\AiQuestionGenerationRequest;
use App\Services\AiQuestionGeneration\AiGeneratedQuestionNormalizer;
use App\Services\AiQuestionGeneration\Providers\CloudflareWorkersAiQuestionGenerationProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CloudflareWorkersAiQuestionGenerationProviderTest extends TestCase
{
    public function test_it_sends_single_image_as_raw_image_to_vision_model(): void
    {
        $this->configureCloudflare();

        Storage::fake('local');
        Storage::disk('local')->put('sample.png', 'fake-image-bytes');

        Http::fake([
            'https://cloudflare.test/client/v4/accounts/account-123/ai/run/@cf/meta/llama-3.2-11b-vision-instruct' => $this->cloudflareRunResponse(),
        ]);

        $provider = new CloudflareWorkersAiQuestionGenerationProvider(
            normalizer: new AiGeneratedQuestionNormalizer()
        );

        $result = $provider->generate($this->generationRequest(
            sourceType: 'Images',
            assets: [
                new AiQuestionGenerationAsset([
                    'storage_disk' => 'local',
                    'storage_path' => 'sample.png',
                    'original_name' => 'sample.png',
                    'mime_type' => 'image/png',
                    'position' => 1,
                ]),
            ]
        ));

        $this->assertSame('raw_image', $result['input_mode']);

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            return $request->url() === 'https://cloudflare.test/client/v4/accounts/account-123/ai/run/@cf/meta/llama-3.2-11b-vision-instruct'
                && $request->hasHeader('Authorization', 'Bearer cf-test-key')
                && isset($payload['image'])
                && str_starts_with((string) $payload['image'], 'data:image/png;base64,')
                && str_contains((string) $payload['messages'][0]['content'], 'استخدم الصورة المرفقة فقط');
        });
    }

    public function test_it_converts_pdf_to_markdown_then_sends_text_to_model(): void
    {
        $this->configureCloudflare();

        Storage::fake('local');
        Storage::disk('local')->put('sample.pdf', '%PDF fake bytes');

        Http::fake([
            'https://cloudflare.test/client/v4/accounts/account-123/ai/tomarkdown' => Http::response([
                'success' => true,
                'result' => [
                    [
                        'name' => 'sample.pdf',
                        'mimeType' => 'application/pdf',
                        'format' => 'markdown',
                        'data' => '# Extracted PDF lesson',
                    ],
                ],
            ], 200),
            'https://cloudflare.test/client/v4/accounts/account-123/ai/run/@cf/meta/llama-3.2-11b-vision-instruct' => $this->cloudflareRunResponse(),
        ]);

        $provider = new CloudflareWorkersAiQuestionGenerationProvider(
            normalizer: new AiGeneratedQuestionNormalizer()
        );

        $result = $provider->generate($this->generationRequest(
            sourceType: 'Pdf',
            assets: [
                new AiQuestionGenerationAsset([
                    'storage_disk' => 'local',
                    'storage_path' => 'sample.pdf',
                    'original_name' => 'sample.pdf',
                    'mime_type' => 'application/pdf',
                    'position' => 1,
                ]),
            ]
        ));

        $this->assertSame('toMarkdown', $result['input_mode']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://cloudflare.test/client/v4/accounts/account-123/ai/tomarkdown'
                && $request->hasHeader('Authorization', 'Bearer cf-test-key');
        });

        Http::assertSent(function ($request): bool {
            $payload = $request->data();
            $content = (string) ($payload['messages'][0]['content'] ?? '');

            return $request->url() === 'https://cloudflare.test/client/v4/accounts/account-123/ai/run/@cf/meta/llama-3.2-11b-vision-instruct'
                && ! isset($payload['image'])
                && str_contains($content, '# Extracted PDF lesson');
        });
    }

    private function configureCloudflare(): void
    {
        Config::set('ai_question_generation.cloudflare_workers_ai.api_key', 'cf-test-key');
        Config::set('ai_question_generation.cloudflare_workers_ai.account_id', 'account-123');
        Config::set('ai_question_generation.cloudflare_workers_ai.base_url', 'https://cloudflare.test/client/v4');
        Config::set('ai_question_generation.cloudflare_workers_ai.model', '@cf/meta/llama-3.2-11b-vision-instruct');
        Config::set('ai_question_generation.cloudflare_workers_ai.timeout_seconds', 10);
        Config::set('ai_question_generation.cloudflare_workers_ai.temperature', 0.3);
        Config::set('ai_question_generation.cloudflare_workers_ai.max_tokens', 1200);
        Config::set('ai_question_generation.cloudflare_workers_ai.supported_source_types', ['Images', 'Pdf']);
    }

    /**
     * @param array<int, AiQuestionGenerationAsset> $assets
     */
    private function generationRequest(string $sourceType, array $assets): AiQuestionGenerationRequest
    {
        $request = new AiQuestionGenerationRequest([
            'source_type' => $sourceType,
            'requested_question_count' => 5,
            'difficulty_level' => 'Easy',
            'language' => 'Arabic',
        ]);

        $request->setRelation('assets', new Collection($assets));

        return $request;
    }

    private function cloudflareRunResponse(): mixed
    {
        return Http::response([
            'success' => true,
            'result' => [
                'response' => json_encode([
                    'content_type' => 'Educational',
                    'questions' => [
                        [
                            'question_text' => 'سؤال؟',
                            'hint_text' => null,
                            'options' => [
                                ['option_text' => 'صحيح', 'is_correct' => true],
                                ['option_text' => 'خطأ', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question_text' => 'سؤال ثان؟',
                            'hint_text' => null,
                            'options' => [
                                ['option_text' => 'صحيح', 'is_correct' => true],
                                ['option_text' => 'خطأ', 'is_correct' => false],
                            ],
                        ],
                        [
                            'question_text' => 'سؤال ثالث؟',
                            'hint_text' => null,
                            'options' => [
                                ['option_text' => 'صحيح', 'is_correct' => true],
                                ['option_text' => 'خطأ', 'is_correct' => false],
                            ],
                        ],
                    ],
                ]),
            ],
        ], 200);
    }
}
