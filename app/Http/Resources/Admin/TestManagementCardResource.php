<?php

namespace App\Http\Resources\Admin;

use App\Helpers\DateProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestManagementCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,

            'scientific_interests' => $this->testIntersetSelections->pluck('interest.name')->filter()->values()->toArray(),

            'difficulty_level' => $this->difficulty_level,
            'question_count' => (int) $this->question_count,
            'average_rating' => round((float) ($this->average_rating ?? 0), 1),
            'price' => $this->price ?? 0,

            'created_at' => DateProcessor::fromTimestamp($this->created_at),
        ];
    }
}
