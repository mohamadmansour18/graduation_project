<?php

namespace App\Http\Resources;

use App\Helpers\DateProcessor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudyPlanOverviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $startDate = Carbon::parse($this->start_date)->startOfDay();
        $endDate = Carbon::parse($this->end_date)->startOfDay();
        $today = today();

        return [
            'id' => $this->id,
            'emoji' => $this->emoji,
            'title' => $this->title,

            'subjects_count' => (int) $this->subjects_count,

            'daily_study_minutes' => (int) $this->daily_study_minutes,
            'daily_study_hours' => round($this->daily_study_minutes / 60, 2),

            'duration_days' => $startDate->diffInDays($endDate) + 1,

            'start_date' => DateProcessor::fromTimestamp($startDate),
            'end_date' => DateProcessor::fromTimestamp($endDate),

            'starts_in_days' => $today->diffInDays($startDate, false),
            'remaining_days' => max(0, $today->diffInDays($endDate, false)),

            'is_default' => (bool) $this->is_default,

            'statistics' => [
                'tasks_count' => (int) $this->tasks_count,
                'completed_tasks_count' => (int) $this->completed_tasks_count,
                'missed_tasks_count' => (int) $this->missed_tasks_count,
                'pending_tasks_count' => (int) $this->pending_tasks_count,
            ],
        ];
    }
}
