<?php

namespace App\Http\Resources\TestDiscovery;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LabRecommendedTestListItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'test' => [
                'id' => $this['test_id'],
                'title' => $this['test_title'],
                'description' => $this['test_description'],
                'difficulty_level' => $this['difficulty_level'],
                'interest_names' => $this['interest_names'],
                'question_count' => $this['question_count'],
                'published_at' => $this['published_at'],
                'published_at_human' => $this->formatPublishedAtHuman($this['published_at'] ?? null),
                'average_rating' => $this['average_rating'],
                'price' => $this['price'],
            ],

            'recommendation' => [
                'score' => $this['recommendation']['score'],
                'score_breakdown' => $this['recommendation']['score_breakdown'],
                'candidate_bucket' => $this['recommendation']['candidate_bucket'],
                'matched_interest_ids' => $this['recommendation']['matched_interest_ids'],
                'matched_interests_count' => $this['recommendation']['matched_interests_count'],
                'matched_by_target_level' => $this['recommendation']['matched_by_target_level'],
            ],
        ];
    }

    private function formatPublishedAtHuman(?string $publishedAt): ?string
    {
        if ($publishedAt === null || trim($publishedAt) === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::parse($publishedAt)->locale('ar');
        } catch (\Throwable) {
            return null;
        }

        if ($date->greaterThanOrEqualTo(now()->subDays(7))) {
            return $date->diffForHumans();
        }

        return $date->translatedFormat('d F Y');
    }
}
