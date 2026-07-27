<?php

namespace App\Http\Resources\LibraryMaterial;

use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\VisibilityType;
use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyLibraryMaterialDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isPublic = $this->visibility_type->value === VisibilityType::Public->value;

        return [
            'basic_info' => [
                'id' => $this->id,
                'title' => $this->title,
                'description' => $this->description,

                'interests' => $this->interests->pluck('name')->toArray(),

                'target_level' => $this->target_level,

                'content_kind' => $this->content_kind,

                'visibility_type' => $this->visibility_type === VisibilityType::Private ? 'محتوى خاص' : 'محتوى عام',

                'asset_count' => (int) $this->asset_count,

                'published_at' => DateProcessor::fromTimestamp($this->published_at),

                'assets' => $this->libraryMaterialAssets->map(fn ($asset) => [
                    'id' => $asset->id,
                    'url' => ImageProcessor::url($asset->storage_path , $asset->storage_disk),
                    'position' => (int) $asset->position,
                ]),

                ...($isPublic ? [
                    'like_count' => (int) $this->like_count,
                    'bookmarks_count' => (int) $this->bookmarks_count,
                    'download_count' => (int) $this->download_count,
                    'review_status' => $this->review_status,
                ] : []),
            ],

            ...($isPublic ? [
                'status_history' => $this->libraryMaterialStatusHistories
                    ->sortByDesc('created_at')
                    ->values()
                    ->map(fn ($history) => [
                        'id' => $history->id,
                        'from_status' => $history->from_status,
                        'to_status' => $history->to_status,
                        'note' => $history->note,
                        'happened_at' => DateProcessor::fromTimestamp($history->created_at),
                    ]),
            ] : []),
        ];
    }

}
