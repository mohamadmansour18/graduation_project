<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LibraryMaterialActivityMonthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'month_no' => (int) data_get($this->resource, 'month_no'),
            'published_materials_count' => (int) data_get($this->resource, 'published_materials_count', 0),
            'likes_count' => (int) data_get($this->resource, 'likes_count', 0),
        ];
    }
}
