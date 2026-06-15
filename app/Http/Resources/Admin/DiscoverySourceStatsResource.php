<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscoverySourceStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => data_get($this->resource, 'key'),
            'label' => data_get($this->resource, 'label'),
            'users_count' => (int) data_get($this->resource, 'users_count', 0),
        ];
    }
}
