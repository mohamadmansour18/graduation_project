<?php

namespace App\Services\AiQuestionGeneration\Routing;

use App\Models\AiQuestionGenerationAsset;
use App\Models\AiQuestionGenerationRequest;
use App\Services\AiQuestionGeneration\Support\AiProviderCapabilityService;
use Illuminate\Support\Facades\Log;

class AiQuestionGenerationRoutingPolicy
{
    public function __construct(
        private readonly AiProviderCapabilityService $providerCapabilityService
    ) {}

    /**
     * Build ordered provider chain based on request complexity score.
     *
     * @return array<int, string>
     */
    public function buildProviderChain(AiQuestionGenerationRequest $generationRequest): array
    {
        $scoreBreakdown = $this->scoreBreakdown($generationRequest);
        $score = array_sum($scoreBreakdown);
        $level = $this->levelForScore($score);

        $chainSelection = $this->configuredChainFor(
            sourceType: $generationRequest->source_type,
            level: $level
        );

        $chain = $chainSelection['chain'];
        $rawChain = $chain;
        $filterResult = $this->providerCapabilityService->filterProviderChainWithDetails(
            providerNames: $chain,
            sourceType: $generationRequest->source_type,
            context: [
                'generation_request_id' => $generationRequest->id,
                'complexity_score' => $score,
                'complexity_level' => $level,
                'chain_source' => $chainSelection['source'],
            ]
        );
        $chain = $filterResult['provider_chain'];

        if ($chain === []) {
            $fallbackResult = $this->fallbackProviderChain($generationRequest);
            $chain = $fallbackResult['provider_chain'];
        } else {
            $fallbackResult = null;
        }

        Log::info('AI question generation provider chain selected.', [
            'generation_request_id' => $generationRequest->id,
            'source_type' => $generationRequest->source_type,
            'requested_question_count' => $generationRequest->requested_question_count,
            'difficulty_level' => $generationRequest->difficulty_level,
            'assets_count' => $generationRequest->assets->count(),
            'total_assets_size_bytes' => (int) $generationRequest->assets->sum('size_bytes'),
            'complexity_score' => $score,
            'score_breakdown' => $scoreBreakdown,
            'complexity_level' => $level,
            'chain_source' => $chainSelection['source'],
            'chain_reason' => $chainSelection['reason'],
            'raw_provider_chain' => $rawChain,
            'provider_chain' => $chain,
            'accepted_providers' => $filterResult['accepted_providers'],
            'skipped_providers' => $filterResult['skipped_providers'],
            'fallback_used' => $fallbackResult !== null,
            'fallback_provider_chain' => $fallbackResult['provider_chain'] ?? [],
            'fallback_reason' => $fallbackResult['reason'] ?? null,
        ]);

        return $chain;
    }

    /**
     * @return array{chain: array<int, string>, source: string, reason: string}
     */
    private function configuredChainFor(string $sourceType, string $level): array
    {
        $sourceTypeChain = config("ai_question_generation.provider_routing.chains_by_source_type.{$sourceType}.{$level}", []);

        if (is_array($sourceTypeChain) && $sourceTypeChain !== []) {
            return [
                'chain' => $sourceTypeChain,
                'source' => 'chains_by_source_type',
                'reason' => "Using source-specific {$sourceType} {$level} chain.",
            ];
        }

        $chain = config("ai_question_generation.provider_routing.chains.{$level}", []);

        if (is_array($chain) && $chain !== []) {
            return [
                'chain' => $chain,
                'source' => 'chains',
                'reason' => "Source-specific chain missing; using legacy {$level} chain.",
            ];
        }

        $highChain = config('ai_question_generation.provider_routing.chains.high', [
            'gemini',
            'deepseek',
            'ollama_cloud',
            'ollama_local',
        ]);

        return [
            'chain' => is_array($highChain) ? $highChain : [],
            'source' => 'chains.high',
            'reason' => 'Requested chain missing; using high chain as final configured fallback.',
        ];
    }

    /**
     * @return array{provider_chain: array<int, string>, reason: string}
     */
    private function fallbackProviderChain(AiQuestionGenerationRequest $generationRequest): array
    {
        $fallbackProvider = (string) config('ai_question_generation.provider_routing.fallback_provider', '');

        $fallbackResult = $this->providerCapabilityService->filterProviderChainWithDetails(
            providerNames: [$fallbackProvider],
            sourceType: $generationRequest->source_type,
            context: [
                'generation_request_id' => $generationRequest->id,
                'fallback_provider' => $fallbackProvider,
                'chain_source' => 'fallback_provider',
            ]
        );

        if ($fallbackResult['provider_chain'] !== []) {
            return [
                'provider_chain' => $fallbackResult['provider_chain'],
                'reason' => 'Configured chain became empty after capability filtering; using fallback provider.',
            ];
        }

        return [
            'provider_chain' => $this->providerCapabilityService->registeredProvidersSupporting(
                sourceType: $generationRequest->source_type
            ),
            'reason' => 'Fallback provider is unavailable for this source type; using any registered compatible provider.',
        ];
    }

    public function score(AiQuestionGenerationRequest $generationRequest): int
    {
        return array_sum($this->scoreBreakdown($generationRequest));
    }

    /**
     * @return array<string, int>
     */
    public function scoreBreakdown(AiQuestionGenerationRequest $generationRequest): array
    {
        return [
            'question_count' => $this->questionCountScore($generationRequest),
            'difficulty' => $this->difficultyScore($generationRequest),
            'source_type' => $this->sourceTypeScore($generationRequest),
            'assets_count' => $this->assetsCountScore($generationRequest),
            'assets_size' => $this->assetsSizeScore($generationRequest),
        ];
    }

    private function questionCountScore(AiQuestionGenerationRequest $generationRequest): int
    {
        $questionCount = (int) $generationRequest->requested_question_count;

        if ($questionCount > 30) {
            return (int) config('ai_question_generation.provider_routing.scoring.question_count.more_than_30', 3);
        }

        if ($questionCount > 20) {
            return (int) config('ai_question_generation.provider_routing.scoring.question_count.more_than_20', 2);
        }

        if ($questionCount > 10) {
            return (int) config('ai_question_generation.provider_routing.scoring.question_count.more_than_10', 1);
        }

        return 0;
    }

    private function difficultyScore(AiQuestionGenerationRequest $generationRequest): int
    {
        return (int) config(
            "ai_question_generation.provider_routing.scoring.difficulty.{$generationRequest->difficulty_level}",
            1
        );
    }

    private function sourceTypeScore(AiQuestionGenerationRequest $generationRequest): int
    {
        return (int) config(
            "ai_question_generation.provider_routing.scoring.source_type.{$generationRequest->source_type}",
            1
        );
    }

    private function assetsCountScore(AiQuestionGenerationRequest $generationRequest): int
    {
        $assetsCount = $generationRequest->assets->count();

        if ($assetsCount > 2) {
            return (int) config('ai_question_generation.provider_routing.scoring.assets_count.more_than_2', 2);
        }

        if ($assetsCount > 1) {
            return (int) config('ai_question_generation.provider_routing.scoring.assets_count.more_than_1', 1);
        }

        return 0;
    }

    private function assetsSizeScore(AiQuestionGenerationRequest $generationRequest): int
    {
        $totalSizeKb = (int) ceil(
            $generationRequest->assets->sum(
                fn (AiQuestionGenerationAsset $asset) => (int) $asset->size_bytes
            ) / 1024
        );

        if ($totalSizeKb > 8192) {
            return (int) config('ai_question_generation.provider_routing.scoring.total_assets_size_kb.more_than_8192', 3);
        }

        if ($totalSizeKb > 4096) {
            return (int) config('ai_question_generation.provider_routing.scoring.total_assets_size_kb.more_than_4096', 2);
        }

        if ($totalSizeKb > 1024) {
            return (int) config('ai_question_generation.provider_routing.scoring.total_assets_size_kb.more_than_1024', 1);
        }

        return 0;
    }

    private function levelForScore(int $score): string
    {
        $lowMax = (int) config('ai_question_generation.provider_routing.score_thresholds.low_max', 2);
        $mediumMax = (int) config('ai_question_generation.provider_routing.score_thresholds.medium_max', 5);

        if ($score <= $lowMax) {
            return 'low';
        }

        if ($score <= $mediumMax) {
            return 'medium';
        }

        return 'high';
    }

}
