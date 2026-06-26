<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use App\Helpers\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardUserLibraryMaterialsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'stats' => [
                'total_materials_count' => $this->resource['stats']['total_materials_count'],
                'files_count' => $this->resource['stats']['files_count'],
                'image_groups_count' => $this->resource['stats']['image_groups_count'],
            ],

            'materials' => $this->resource['materials']->map(function ($material) {
                $contentKind = $material->content_kind?->value ?? $material->content_kind;
                $visibilityType = $material->visibility_type?->value ?? $material->visibility_type;

                $defaultPath = $contentKind === 'ملف'
                    ? 'defaults/File_Default.svg'
                    : 'defaults/Image_Default.svg';

                return [
                    'id' => $material->id,

                    'url_content' => ImageProcessor::urlOrDefault(
                        $material?->firstAsset?->storage_path,
                        $defaultPath,
                        $material?->firstAsset?->storage_disk,
                    ),

                    'title' => $material->title,
                    'description' => $material->description,

                    'type' => $contentKind === 'ملف' ? 'ملف' : 'صورة',
                    'library_material_kind' => $visibilityType,

                    'interests' => $material->interests->pluck('name')->values()->toArray(),

                    'like_count' => (int) $material->like_count,
                    'published_at' => DateProcessor::fromTimestamp($material->published_at) ?? DateProcessor::fromTimestamp($material->created_at),
                ];
            })->values(),
        ];
    }
}
