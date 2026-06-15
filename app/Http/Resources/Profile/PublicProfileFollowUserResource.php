<?php

namespace App\Http\Resources\Profile;

use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProfileFollowUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->id,
            'avatar_url' => ImageProcessor::urlOrDefault($this->userProfile?->avatar_path, 'defaults/default-avatar.svg'),
            'name' => $this->name,
            'is_academically_verified' => (bool) $this->is_academically_verified,
            'education_level' => $this->userOnboardingProfile?->education_level,
            'viewer_is_following' => (bool) $this->viewer_is_following,
        ];
    }
}
