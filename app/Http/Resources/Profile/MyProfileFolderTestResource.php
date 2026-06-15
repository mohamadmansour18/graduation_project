<?php

namespace App\Http\Resources\Profile;

use App\Helpers\DateProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyProfileFolderTestResource extends JsonResource
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

            'title' => $this->title,
            'description' => $this->description,

            'interests' => $this->testIntersetSelections->pluck('interest.name')->filter()->values()->toArray(),

            'difficulty_level' => $this->difficulty_level,
            'question_count' => (int) $this->question_count,
            'average_rating' => round((float) ($this->average_rating ?? 0), 1),
            'price' => (float) ($this->price ?? 0),
            'published_at' => DateProcessor::fromTimestamp($this->created_at),

            'test_type' => $this->test_type,
        ];
    }
}
