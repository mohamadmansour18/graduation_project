<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class YearlyTestActivityMonthResource extends JsonResource
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
            'published_tests_count' => (int) data_get($this->resource, 'published_tests_count', 0),
            'likes_count' => (int) data_get($this->resource, 'likes_count', 0),
            'reviews_count' => (int) data_get($this->resource, 'reviews_count', 0),
            'downloads_count' => (int) data_get($this->resource, 'downloads_count', 0),
        ];
    }
}
