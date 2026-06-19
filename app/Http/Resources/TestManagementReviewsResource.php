<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestManagementReviewsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $commentsPaginator = $this->resource['comments'];

        return [
            'rating_information' => $this->resource['rating_information'],

            'statistics' => $this->resource['statistics'],

            'comments' => [
                'items' => $commentsPaginator
                    ->getCollection()
                    ->map(function ($review) {
                        return [
                            'id' => $review->id,

                            'name' => $review->user?->name,
                            'avatar' => ImageProcessor::urlOrDefault($review->user?->userProfile?->avatar_path, 'defaults/default-avatar.svg', $review->user?->userProfile?->avatar_disk),
                            'is_academically_verified' => (bool) ($review->user?->is_academically_verified ?? false),

                            'rating' => (int) $review->rating,
                            'review_text' => $review->review_text,
                            'created_at' => DateProcessor::fromTimestamp($review->created_at),

                            'helpful_yes_count' => (int) ($review->helpful_yes_count ?? 0),
                        ];
                    })
                    ->values(),

                'meta' => [
                    'per_page' => $commentsPaginator->perPage(),
                    'next_cursor' => optional($commentsPaginator->nextCursor())->encode(),
                    'previous_cursor' => optional($commentsPaginator->previousCursor())->encode(),
                    'has_more_pages' => $commentsPaginator->hasMorePages(),
                ],
            ],
        ];
    }
}
