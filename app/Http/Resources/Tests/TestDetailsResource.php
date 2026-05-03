<?php

namespace App\Http\Resources\Tests;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $test = $this->resource['test'];
        $context = $this->resource['viewer_context'];

        return [
            'id' => $test->id,

            'basic_info' => [
                'title' => $test->title,
                'description' => $test->description,
                'difficulty_level' => $test->difficulty_level,
                'price' => $test->price ?? 0,
                'likes_count' => $test->likes_count ?? 0,
                'reviews_count' => $test->reviews_count ?? 0,
                'bookmarks_count' => $test->bookmarks_count ?? 0,
            ],

            'creator' => [
                'id' => $test->creatorUser->id,
                'name' => $test->creatorUser->name,
                'is_academically_verified' => (bool) $test->creatorUser->is_academically_verified ,
                'followers_count' => $test->creatorUser->userProfileStat?->followers_count ?? 0,
                'following_count' => $test->creatorUser->userProfileStat?->following_count ?? 0,
                'published_tests_count' => $test->creatorUser->userProfileStat?->published_tests_count ?? 0,
                'profile_picture' => ImageProcessor::urlOrDefault($test?->creatorUser?->userProfile?->avatar_path),
            ],

            'extra_info' => [
                'question_count' => $test->question_count,
                'duration_seconds' => $test->duration_seconds ?? "غير محدد",
                'pass_mark_percentage' => $test->pass_mark_percentage ?? "غير محدد",
                'published_at' => DateProcessor::fromTimestamp($test->published_at) ?? "",
                'last_content_updated_at' => DateProcessor::fromTimestamp($test->last_content_updated_at) ?? "لم يتم تعديل المحتوى بعد",
                'target_level' => $test->target_level,
                'language' => $test->language,
                'participants_count' => $test->participants_count ?? 0,
                'review_status' => $test->review_status,
                'interests' => $test->interests->map(fn ($interest) => [
                    'id' => $interest->id,
                    'name' => $interest->name,
                ])->values(),

                'viewer_context' => $context,
            ]
        ];
    }
}
