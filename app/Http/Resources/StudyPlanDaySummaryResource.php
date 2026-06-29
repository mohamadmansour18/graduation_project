<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudyPlanDaySummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this['date'],
            'day_number' => $this['day_number'],
            'day_name' => $this['day_name'],

            'is_today' => (bool) $this['is_today'],
            'has_tasks' => (bool) $this['has_tasks'],

            'total_tasks' => (int) $this['total_tasks'],
            'completed_tasks' => (int) $this['completed_tasks'],

            'completion_state' => $this['completion_state'],

            // today | empty | completed | incomplete | scheduled
            'display_state' => $this['display_state'],
        ];
    }
}
