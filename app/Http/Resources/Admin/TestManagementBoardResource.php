<?php

namespace App\Http\Resources\Admin;

use App\Helpers\DateProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestManagementBoardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'selected_date' => $this->resource['selected_date'],

            'columns' => collect($this->resource['columns'])
                ->map(function ($items) {
                    return [
                        'count' => $items->count(),
                        'items' => TestManagementCardResource::collection($items),
                    ];
                })
                ->all(),
        ];
    }

}
