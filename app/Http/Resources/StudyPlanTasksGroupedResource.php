<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudyPlanTasksGroupedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'old' => [
                'count' => $this['old']->count(),
                'tasks' => DailyTaskResource::collection($this['old']),
            ],

            'upcoming' => [
                'count' => $this['upcoming']->count(),
                'tasks' => DailyTaskResource::collection($this['upcoming']),
            ],

            'completed' => [
                'count' => $this['completed']->count(),
                'tasks' => DailyTaskResource::collection($this['completed']),
            ],
        ];
    }
}
