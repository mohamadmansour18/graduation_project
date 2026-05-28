<?php

namespace Tests\Unit;

use App\Models\AiQuestionGenerationAsset;
use App\Models\AiQuestionGenerationRequest;
use App\Services\AiQuestionGeneration\Routing\AiQuestionGenerationRoutingPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AiQuestionGenerationRoutingPolicyTest extends TestCase
{
    public function test_it_uses_image_specific_low_complexity_chain_with_ollama_local_last(): void
    {
        $this->configureRouting();

        $request = $this->generationRequest(sourceType: 'Images');

        $chain = app(AiQuestionGenerationRoutingPolicy::class)
            ->buildProviderChain($request);

        $this->assertSame([
            'openrouter',
            'cloudflare_workers_ai',
            'gemini',
            'ollama_cloud',
            'huggingface',
            'deepseek',
            'ollama_local',
        ], $chain);
    }

    public function test_it_uses_pdf_specific_low_complexity_chain_without_ollama_local(): void
    {
        $this->configureRouting();

        $request = $this->generationRequest(sourceType: 'Pdf');

        $chain = app(AiQuestionGenerationRoutingPolicy::class)
            ->buildProviderChain($request);

        $this->assertSame([
            'cloudflare_workers_ai',
            'gemini',
            'ollama_cloud',
            'huggingface',
            'deepseek',
        ], $chain);
    }

    public function test_it_falls_back_to_legacy_chain_when_source_specific_chain_is_missing(): void
    {
        $this->configureRouting(chainsBySourceType: [
            'Images' => [],
            'Pdf' => [],
        ]);

        $request = $this->generationRequest(sourceType: 'Images');

        $chain = app(AiQuestionGenerationRoutingPolicy::class)
            ->buildProviderChain($request);

        $this->assertSame([
            'openrouter',
            'gemini',
            'deepseek',
            'ollama_local',
        ], $chain);
    }

    /**
     * @param array<string, array<int, string>> $chains
     * @param array<string, array<string, array<int, string>>>|null $chainsBySourceType
     */
    private function configureRouting(array $chains = [], ?array $chainsBySourceType = null): void
    {
        Config::set('ai_question_generation.providers', [
            'openrouter' => self::class,
            'cloudflare_workers_ai' => self::class,
            'deepseek' => self::class,
            'gemini' => self::class,
            'ollama_cloud' => self::class,
            'huggingface' => self::class,
            'ollama_local' => self::class,
        ]);

        Config::set('ai_question_generation.provider_capabilities', [
            'openrouter' => [
                'source_types' => ['Images'],
                'input_modes' => ['raw_image'],
            ],
            'cloudflare_workers_ai' => [
                'source_types' => ['Images', 'Pdf'],
                'input_modes' => ['raw_image', 'raw_file'],
            ],
            'deepseek' => [
                'source_types' => ['Images', 'Pdf'],
                'input_modes' => ['extracted_text'],
            ],
            'gemini' => [
                'source_types' => ['Images', 'Pdf'],
                'input_modes' => ['raw_image', 'raw_file'],
            ],
            'ollama_local' => [
                'source_types' => ['Images'],
                'input_modes' => ['raw_image'],
            ],
            'ollama_cloud' => [
                'source_types' => ['Images', 'Pdf'],
                'input_modes' => ['extracted_text'],
            ],
            'huggingface' => [
                'source_types' => ['Images', 'Pdf'],
                'input_modes' => ['extracted_text'],
            ],
        ]);
        Config::set('ai_question_generation.runtime_input_modes.Images', ['raw_image', 'extracted_text']);
        Config::set('ai_question_generation.runtime_input_modes.Pdf', ['raw_file', 'extracted_text']);

        Config::set('ai_question_generation.provider_routing.fallback_provider', 'ollama_local');
        Config::set('ai_question_generation.provider_routing.chains', array_replace([
            'low' => ['openrouter', 'gemini', 'deepseek', 'ollama_local'],
            'medium' => ['openrouter', 'gemini', 'deepseek'],
            'high' => ['gemini', 'openrouter', 'deepseek'],
        ], $chains));
        Config::set('ai_question_generation.provider_routing.chains_by_source_type', $chainsBySourceType ?? [
            'Images' => [
                'low' => [
                    'openrouter',
                    'cloudflare_workers_ai',
                    'gemini',
                    'ollama_cloud',
                    'huggingface',
                    'deepseek',
                    'ollama_local',
                ],
                'medium' => [
                    'openrouter',
                    'cloudflare_workers_ai',
                    'gemini',
                    'ollama_cloud',
                    'huggingface',
                    'deepseek',
                    'ollama_local',
                ],
                'high' => [
                    'gemini',
                    'openrouter',
                    'cloudflare_workers_ai',
                    'ollama_cloud',
                    'huggingface',
                    'deepseek',
                    'ollama_local',
                ],
            ],
            'Pdf' => [
                'low' => [
                    'cloudflare_workers_ai',
                    'gemini',
                    'ollama_cloud',
                    'huggingface',
                    'deepseek',
                ],
                'medium' => [
                    'cloudflare_workers_ai',
                    'gemini',
                    'ollama_cloud',
                    'huggingface',
                    'deepseek',
                ],
                'high' => [
                    'gemini',
                    'cloudflare_workers_ai',
                    'ollama_cloud',
                    'huggingface',
                    'deepseek',
                ],
            ],
        ]);
        Config::set('ai_question_generation.provider_routing.score_thresholds.low_max', 2);
        Config::set('ai_question_generation.provider_routing.score_thresholds.medium_max', 5);
        Config::set('ai_question_generation.provider_routing.scoring.question_count.more_than_10', 1);
        Config::set('ai_question_generation.provider_routing.scoring.question_count.more_than_20', 2);
        Config::set('ai_question_generation.provider_routing.scoring.question_count.more_than_30', 3);
        Config::set('ai_question_generation.provider_routing.scoring.difficulty.Easy', 0);
        Config::set('ai_question_generation.provider_routing.scoring.difficulty.Medium', 1);
        Config::set('ai_question_generation.provider_routing.scoring.difficulty.Hard', 3);
        Config::set('ai_question_generation.provider_routing.scoring.source_type.Images', 1);
        Config::set('ai_question_generation.provider_routing.scoring.source_type.Pdf', 2);
        Config::set('ai_question_generation.provider_routing.scoring.assets_count.more_than_1', 1);
        Config::set('ai_question_generation.provider_routing.scoring.assets_count.more_than_2', 2);
        Config::set('ai_question_generation.provider_routing.scoring.total_assets_size_kb.more_than_1024', 1);
        Config::set('ai_question_generation.provider_routing.scoring.total_assets_size_kb.more_than_4096', 2);
        Config::set('ai_question_generation.provider_routing.scoring.total_assets_size_kb.more_than_8192', 3);
    }

    private function generationRequest(string $sourceType): AiQuestionGenerationRequest
    {
        $request = new AiQuestionGenerationRequest([
            'source_type' => $sourceType,
            'requested_question_count' => 5,
            'difficulty_level' => 'Easy',
            'language' => 'Arabic',
        ]);

        $request->setRelation('assets', new Collection([
            new AiQuestionGenerationAsset([
                'size_bytes' => 512,
                'mime_type' => $sourceType === 'Pdf' ? 'application/pdf' : 'image/png',
            ]),
        ]));

        return $request;
    }
}
