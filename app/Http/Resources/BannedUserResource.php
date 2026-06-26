<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannedUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'user_id' => $user?->id,
            'name' => $user?->name,

            'avatar' => ImageProcessor::urlOrDefault(
                $user?->userProfile?->avatar_path,
                'defaults/default-avatar.svg',
                $user?->userProfile?->avatar_disk,
            ),

            'is_academically_verified' => (bool) $user?->is_academically_verified,
            'education_level' => $user?->userOnboardingProfile?->education_level,

            'ban_type' => $this->ban_type,

            'ban_ends_at' => $this->ban_type === 'حظر دائم'
                ? 'حظر دائم'
                : DateProcessor::fromTimestamp($this->ends_at),
        ];
    }
}
