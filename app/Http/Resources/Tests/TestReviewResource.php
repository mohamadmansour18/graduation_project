<?php

namespace App\Http\Resources\Tests;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewerFeedback = $this->testReviewFeedback;

        return [
            'id' => $this->id,
            'rating' => (int) $this->rating,
            'review_text' => $this->review_text ,
            'created_at' => DateProcessor::fromTimestamp($this->created_at),
            'yes_count' => (int) $this->helpful_yes_count ?? 0,

            'reviewer' => [
                'id' => $this?->user->id,
                'name' => $this?->user->name,
                'avatar_url' => ImageProcessor::urlOrDefault($this->user?->userProfile?->avatar_path) ,
                'is_academically_verified' => (bool) $this?->user->is_academically_verified,
            ],

            'viewer_feedback' => [
                'has_voted' => $viewerFeedback !== null,
                'vote' => $viewerFeedback?->vote,
            ],
        ];
    }
}
