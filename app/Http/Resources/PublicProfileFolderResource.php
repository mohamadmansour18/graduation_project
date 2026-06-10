<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProfileFolderResource extends JsonResource
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
            'tests_count' => (int) $this->tests_count,
            'published_at' => DateProcessor::fromTimestamp($this->published_at),
            'scientific_interests' => $this->scientific_interests ?? [],
            'color_code' => $this->color_code ?? "5583FF",
            'viewer_has_bookmarked' => (bool) $this->viewer_has_bookmarked,
        ];
    }
}
