<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestManagementDetailsResource extends JsonResource
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
                'name' => $this->creatorUser?->name,
                'avatar' => ImageProcessor::urlOrDefault($this->creatorUser?->userProfile?->avatar_path, 'defaults/default-avatar.svg' , $this->creatorUser?->userProfile?->avatar_disk),
                'followers_count' => (int) ($this->creatorUser?->userProfileStat?->followers_count ?? 0),
                'following_count' => (int) ($this->creatorUser?->userProfileStat?->following_count ?? 0),
                'tests_count' => (int) ($this->creatorUser?->userProfileStat?->published_tests_count ?? 0),
                'is_academically_verified' => (bool) ($this->creatorUser?->is_academically_verified ?? false),
            ],

            'basic_information' => [
                'title' => $this->title,
                'description' => $this->description,
                'question_count' => (int) $this->question_count,
                'duration_seconds' => (int) $this->duration_seconds,
                'difficulty_level' => $this->difficulty_level->value,
                'pass_mark_percentage' => (float) $this->pass_mark_percentage,
                'published_at' => DateProcessor::fromTimestamp($this->published_at) ?? "لم ينشر بعد",
                'price' => $this->price ?? "اختبار مجاني",
                'platform_net_profit_amount' => $this->platformNetProfitAmount() ?? "اختبار مجاني",
                'review_status' => $this->review_status->value,
            ],

            'secondary_information' => [
                'interests' => $this->testIntersetSelections->pluck('interest.name')->filter()->values()->toArray(),

                'last_content_updated_at' => DateProcessor::fromTimestamp($this->last_content_updated_at) ?? "لم يتم تحديث الاختبار بعد",
                'target_level' => $this->target_level->value,
                'language' => $this->language->value,
                'participants_count' => (int) ($this->participants_count ?? 0),
            ],

            'statistics' => [
                'likes_count' => (int) ($this->likes_count ?? 0),
                'reviews_count' => (int) ($this->reviews_count ?? 0),
                'bookmarks_count' => (int) ($this->bookmarks_count ?? 0),
                'downloads_count' => (int) ($this->downloads_count ?? 0),
            ],
        ];
    }

    private function platformNetProfitAmount(): ?float
    {
        if (is_null($this->price)) {
            return null;
        }

        $platformFeePercentage = (float) config('payments.platform_fee_percent', 0);

        return round(((float) $this->price * $platformFeePercentage) / 100, 2);
    }
}
