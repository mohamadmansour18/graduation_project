<?php

namespace App\Http\Resources;

use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,

            'avatar_url' => ImageProcessor::urlOrDefault(
                $this?->userProfile?->avatar_path,
                'defaults/default-avatar.svg',
                $this?->userProfile?->avatar_disk
            ),

            'academic_level' => $this?->userOnboardingProfile?->education_level->value,

            'is_academically_verified' => (bool) $this->is_academically_verified,

            'viewer_is_following' => (bool) ($this->viewer_is_following ?? false),
        ];
    }
}
