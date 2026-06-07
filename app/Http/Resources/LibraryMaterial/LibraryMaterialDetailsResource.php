<?php

namespace App\Http\Resources\LibraryMaterial;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryMaterialDetailsResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'basic_info' => [
                'id' => $this->id,
                'title' => $this->title,
                'description' => $this->description,
                'interests' => $this->interests->map(fn ($interest) => [
                    'id' => $interest->id,
                    'name' => $interest->name,
                ]),
                'target_level' => $this->target_level,
                'content_kind' => $this->content_kind,
                'asset_count' => (int) $this->asset_count,
                'like_count' => (int) $this->like_count,
                'bookmarks_count' => (int) $this->bookmarks_count,
                'download_count' => (int) $this->download_count,
                'published_at' => DateProcessor::fromTimestamp($this->published_at),

                'assets' => $this->libraryMaterialAssets->map(fn ($asset) => [
                    'id' => $asset->id,
                    'url' => ImageProcessor::url($asset->storage_path , $asset->storage_disk),
                    'position' => (int) $asset->position,
                ]),
            ],

            'publisher' => [
                'id' => $this->creatorUser->id,
                'name' => $this->creatorUser->name,
                'avatar_url' => ImageProcessor::urlOrDefault($this->creatorUser->userProfile?->avatar_path),
                'followers_count' => (int) optional($this->creatorUser->userProfileStat)->followers_count,
                'following_count' => (int) optional($this->creatorUser->userProfileStat)->following_count,
                'published_tests_count' => (int) optional($this->creatorUser->userProfileStat)->published_tests_count,
            ],

            'viewer_state' => [
                'viewer_has_liked' => (bool) $this->viewer_has_liked,
                'viewer_has_bookmarked' => (bool) $this->viewer_has_bookmarked,
                'viewer_is_following_creator' => (bool) $this->viewer_is_following_creator,
                'creator_is_academically_verified' => (bool) $this->creatorUser->is_academically_verified,
            ],
        ];
    }
}
