<?php

namespace App\Services\AiQuestionGeneration\Routing;

use App\Models\AiQuestionGenerationAsset;
use App\Models\AiQuestionGenerationRequest;
use Illuminate\Support\Facades\Log;

class AiQuestionGenerationRoutingPolicy
{
    /**
     * Build ordered provider chain based on request complexity score.
     *
     * @return array<int, string>
     */
    public function buildProviderChain(AiQuestionGenerationRequest $generationRequest): array
    {
        $score = $this->score($generationRequest);
        $level = $this->levelForScore($score);

        $chain = config("ai_question_generation.provider_routing.chains.{$level}", []);

        if (! is_array($chain) || $chain === []) {
            $chain = config('ai_question_generation.provider_routing.chains.high', [
                'gemini',
                'deepseek',
                'ollama_cloud',
                'ollama_local',
            ]);
        }

        Log::info('AI question generation provider chain selected.', [
            'generation_request_id' => $generationRequest->id,
            'complexity_score' => $score,
            'complexity_level' => $level,
            'provider_chain' => $chain,
        ]);

        return $chain;
    }

    public function score(AiQuestionGenerationRequest $generationRequest): int
    {
        return $this->questionCountScore($generationRequest)
            + $this->difficultyScore($generationRequest)
            + $this->sourceTypeScore($generationRequest)
            + $this->assetsCountScore($generationRequest)
            + $this->assetsSizeScore($generationRequest);
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
