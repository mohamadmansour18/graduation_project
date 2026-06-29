<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalSubtasks = (int) $this->subtasks_total_count;
        $completedSubtasks = (int) $this->completed_subtasks_count;

        return [
            'id' => (int) $this->id,
            'occurrence_id' => (int) $this->occurrence_id,

            // رقم عرض فقط، وليس id من الداتابيز
            'task_number' => $this->task_number ?? null,

            'title' => $this->title,
            'status' => $this->status,
            'priority' => $this->priority,

            'subtasks' => [
                'completed' => $completedSubtasks,
                'total' => $totalSubtasks,
                'label' => $totalSubtasks > 0
                    ? "{$completedSubtasks}/{$totalSubtasks}"
                    : 'لا يوجد',
            ],

            'time' => [
                'start' => $this->formatTime($this->scheduled_start_time),
                'end' => $this->formatTime($this->scheduled_end_time),
                'duration_seconds' => (int) $this->duration_second,
                'duration_minutes' => (int) floor(((int) $this->duration_second) / 60),
            ],
        ];
    }

    private function formatTime(?string $time): ?string
    {
        if (! $time) {
            return null;
        }

        return substr($time, 0, 5);
    }
}
