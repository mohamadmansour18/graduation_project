<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if(empty($this->resource))
        {
            return [];
        }

        return [
            'id' => $this->id,
            'rating' => (int) $this->rating,
            'review_text' => $this->review_text ?? null,
            'created_at' => DateProcessor::fromTimestamp($this->created_at),
            'yes_count' => (int) $this->helpful_yes_count ?? 0,

            'my_account_details' => [
                'id' => $this?->user->id,
                'name' => $this?->user->name,
                'avatar_url' => ImageProcessor::urlOrDefault($this->user?->userProfile?->avatar_path) ,
                'is_academically_verified' => (bool) $this?->user->is_academically_verified,
            ],
        ];
    }
}
