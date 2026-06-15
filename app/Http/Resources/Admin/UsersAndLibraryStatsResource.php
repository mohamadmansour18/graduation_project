<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsersAndLibraryStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'year' => $this->resource['year'],

            'discovery_sources' => [
                'total_users_count' => (int) $this->resource['discovery_sources']['total_users_count'],
                'sources' => DiscoverySourceStatsResource::collection(
                    $this->resource['discovery_sources']['sources']
                ),
            ],

            'gender' => $this->resource['gender'],

            'library_material_yearly_activity' => [
                'totals' => $this->resource['library_material_yearly_activity']['totals'],
                'months' => LibraryMaterialActivityMonthResource::collection(
                    $this->resource['library_material_yearly_activity']['months']
                ),
            ],
        ];
    }
}
