<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyProfileTestResource extends JsonResource
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
            'interests' => $this->testIntersetSelections
                ->map(fn ($selection) => $selection->interest ? [
                    'id' => $selection->interest->id,
                    'name' => $selection->interest->name,
                ] : null)
                ->values()
                ->toArray(),
            'target_level' => $this->target_level,
            'average_rating' => round((float) $this->average_rating, 1),
            'price' => $this->price ?? 0,
            'published_at' => DateProcessor::fromTimestamp($this->created_at),
            'question_count' => (int) $this->question_count,
        ];
    }
}
