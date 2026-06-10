<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProfileTestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'interests' => $this->testIntersetSelections->pluck('interest.name')->filter()->values()->toArray(),
            'target_level' => $this->target_level,
            'average_rating' => round((float) $this->average_rating, 1),
            'price' => $this->price ?? 0,
            'published_at' => DateProcessor::fromTimestamp($this->published_at),
            'question_count' => (int) $this->question_count,
        ];
    }
}
