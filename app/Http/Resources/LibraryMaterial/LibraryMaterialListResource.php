<?php

namespace App\Http\Resources\LibraryMaterial;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryMaterialListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $defaultPath = '';

        match ($this->content_kind->value) {
            'ملف' => $defaultPath = 'defaults/File_Default.svg',
            default => $defaultPath = 'defaults/Image_Default.svg',
        };

        return [
            'id' => $this->id,

            'url_content' => ImageProcessor::urlOrDefault( $this?->firstAsset?->storage_path , $defaultPath),

            'title' => $this->title,
            'description' => $this->description,

            'type' => $this->content_kind->value === 'ملف' ? 'ملف' : 'صورة',
            'interests' => $this->interests->pluck('name')->toArray(),

            'like_count' => (int) $this->like_count,
            'published_at' => DateProcessor::fromTimestamp($this->published_at),

            'viewer_has_bookmarked' => (bool) $this->viewer_has_bookmarked,
        ];
    }

}
