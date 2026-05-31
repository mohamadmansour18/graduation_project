<?php

namespace App\Http\Resources\Tests;

use App\Helpers\DateProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FilteredTestResource extends JsonResource
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
            'difficulty_level' => $this->difficulty_level,
            'price' => $this->price ?? 0,
            'average_rating' => $this->average_rating ?? 0.0,
            'published_at' => DateProcessor::fromTimestamp($this->published_at) ?? "لم يتم نشر الاختبار للعامة بعد",
            'question_count' => $this->question_count,
            'interests' => $this->interests->pluck('name')->values()->toArray(),
        ];
    }
}
