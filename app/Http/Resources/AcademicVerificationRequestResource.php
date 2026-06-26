<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicVerificationRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'verification_request_id' => $this->id,

            'user_id' => $this->user?->id,
            'name' => $this->user?->name,

            'avatar' => ImageProcessor::urlOrDefault(
                $this->user?->userProfile?->avatar_path,
                'defaults/default-avatar.svg',
                $this->user?->userProfile?->avatar_disk,
            ),

            'email' => $this->user?->email,
            'university' => $this->user?->userUniversityProfile?->university_name,
            'department' => $this->user?->userUniversityProfile?->department,
            'governorate' => $this->user?->userProfile?->governorate,
            'gender' => $this->user?->gender,
            'submitted_at' => DateProcessor::fromTimestamp($this->submitted_at),
        ];
    }
}
