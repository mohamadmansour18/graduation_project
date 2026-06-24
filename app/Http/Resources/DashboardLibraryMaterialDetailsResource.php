<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardLibraryMaterialDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'publisher' => [
                'id' => $this->creatorUser?->id,
                'name' => $this->creatorUser?->name,
                'avatar_url' => ImageProcessor::urlOrDefault(
                    $this->creatorUser?->userProfile?->avatar_path,
                    'defaults/default-avatar.svg',
                    $this->creatorUser?->userProfile?->avatar_disk
                ),
                'followers_count' => (int) ($this->creatorUser?->userProfileStat?->followers_count ?? 0),
                'following_count' => (int) ($this->creatorUser?->userProfileStat?->following_count ?? 0),
                'is_academically_verified' => (bool) $this->creatorUser?->is_academically_verified,
            ],

            'content' => [
                'id' => $this->id,
                'title' => $this->title,
                'description' => $this->description,
                'interests' => $this->interests->pluck('name')->values()->toArray(),
                'type' => $this->content_kind->value === 'ملف' ? 'ملف' : 'صورة',
                'target_level' => $this->target_level?->value,
                'visibility_type' => $this->visibility_type?->value,
                'status' => $this->review_status?->value,
                'asset_count' => (int) $this->asset_count,
                'published_at' => DateProcessor::fromTimestamp($this->published_at) ?? DateProcessor::fromTimestamp($this->created_at),

                'assets' => $this->libraryMaterialAssets
                    ->sortBy('position')
                    ->values()
                    ->map(fn ($asset) => [
                        'id' => $asset->id,
                        'original_name' => $asset->original_name,
                        'position' => (int) $asset->position,
                        'url' => ImageProcessor::url($asset->storage_path , $asset->storage_disk) ?? null,
                    ])
                    ->toArray(),
            ],

            'statistics' => [
                'like_count' => (int) $this->like_count ?? 0,
                'bookmarks_count' => (int) $this->bookmarks_count ?? 0,
                'download_count' => (int) $this->download_count ?? 0,
            ],
        ];
    }
}
