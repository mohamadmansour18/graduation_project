<?php

namespace App\Http\Resources\LibraryMaterial;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeaturedLibraryMaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $defaultPath = '';

        match ($this->content_kind) {
            'ملف' => $defaultPath = 'defaults/File_Default.svg',
            default => $defaultPath = 'defaults/Image_Default.svg',
        };

        return [
            'id' => $this->id,

            'url_content' => ImageProcessor::urlOrDefault( $this?->firstAsset?->storage_path , $defaultPath),

            'interests' => $this->interests->pluck('name')->toArray(),

            'like_count' => (int) $this->like_count,
            'bookmarks_count' => (int) $this->bookmarks_count,
            'download_count' => (int) $this->download_count,

            'published_at' => DateProcessor::fromTimestamp($this->published_at),
        ];
    }

}
