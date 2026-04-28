<?php

namespace App\Http\Resources\TestDiscovery;

use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LabFeaturedRecommendedTestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'owner' => [
                'name' => $this['owner_name'],
                'owner_profile_picture' => $this['owner_profile_picture'],
                'is_verified' => $this['is_owner_verified'],
                'published_tests_count' => $this['owner_published_tests_count'],
                'followers_count' => $this['owner_followers_count'],
            ],

            'test' => [
                'id' => $this['test_id'],
                'title' => $this['test_title'],
                'description' => $this['test_description'],
                'interest_names' => $this['interest_names'],
                'difficulty_level' => $this['difficulty_level'],
                'question_count' => $this['question_count'],
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
}
