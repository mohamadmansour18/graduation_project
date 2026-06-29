<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudyPlanDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $startDate = Carbon::parse($this->start_date)->startOfDay();
        $endDate = Carbon::parse($this->end_date)->startOfDay();
        $today = today();

        $totalDays = $startDate->diffInDays($endDate) + 1;

        if ($today->lt($startDate)) {
            $elapsedDays = 0;
        } elseif ($today->gt($endDate)) {
            $elapsedDays = $totalDays;
        } else {
            $elapsedDays = $startDate->diffInDays($today) + 1;
        }

        $completedPercentage = $this->tasks_count > 0
            ? round(($this->completed_tasks_count / $this->tasks_count) * 100, 2)
            : 0;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'emoji' => $this->emoji,

            'subjects' => [
                'count' => (int) $this->subjects_count,
                'label' => "{$this->subjects_count}/10",
                'items' => $this->studyPlanSubjects
                    ->sortBy('slot_no')
                    ->values()
                    ->map(fn ($item) => [
                        'id' => $item->studySubject?->id,
                        'name' => $item->studySubject?->name,
                    ]),
            ],

            'progress' => [
                'remaining_days' => max(0, $today->diffInDays($endDate, false)),
                'elapsed_days' => $elapsedDays,
                'total_days' => $totalDays,
                'elapsed_label' => "{$elapsedDays}/{$totalDays}",

                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),

                'completed_tasks_count' => (int) $this->completed_tasks_count,
                'total_tasks_count' => (int) $this->tasks_count,
                'completed_percentage' => $completedPercentage,
            ],
        ];
    }
}
