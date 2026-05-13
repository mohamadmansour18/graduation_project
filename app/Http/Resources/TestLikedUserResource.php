<?php

namespace App\Http\Resources;

use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestLikedUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'user_id' => $this->user_id,
            'name' => $this->name,

            'avatar_url' => ImageProcessor::urlOrDefault($this->avatar_path),

            'education_level' => $this->education_level,

            'is_academically_verified' => (bool) $this->is_academically_verified,

            'viewer_is_following' => (bool) $this->viewer_is_following,
        ];
    }
}
