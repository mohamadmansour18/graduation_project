<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardUserFoldersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

            'total_folders_count' => (int) $this->resource['stats']['total_folders_count'],

            'folders' => $this->resource['folders']->map(function ($folder) {
                return [
                    'id' => $folder->id,
                    'name' => $folder->name,
                    'tests_count' => (int) $folder->tests_count,
                    'published_at' => DateProcessor::fromTimestamp($folder->created_at),
                    'scientific_interests' => $folder->scientific_interests ?? [],
                    'color_code' => $folder->color_code ?? '#5583FF',
                ];
            })->values(),
        ];
    }
}
