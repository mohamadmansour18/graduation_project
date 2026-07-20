<?php

namespace App\Http\Resources;

use App\Enums\RepeatPattern;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudyTaskEditDetailsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalSubtasks = (int) ($this->subtasks_total_count ?? 0);
        $completedSubtasks = (int) ($this->completed_subtasks_count ?? 0);

        return [
            'basic_info' => [
                'id' => (int) $this->id,
                'title' => $this->title,
                'status' => $this->status,
                'task_number' => (int) ($this->task_number ?? 0),

                'subtasks_count' => [
                    'completed' => $completedSubtasks,
                    'total' => $totalSubtasks,
                    'label' => "{$completedSubtasks}/{$totalSubtasks}",
                ],

                'start_date' => optional($this->start_date)->toDateString(),
                'end_date' => optional($this->end_date)->toDateString(),


                'priority' => $this->priority,
                'description' => $this->description,

                'subject' => [
                    'id' => (int) $this->study_plan_subject_id,
                    'name' => $this->studyPlanSubject?->studySubject?->name,
                ],
            ],

            'timing_info' => [
                'start_time' => $this->formatTime($this->start_time),

                'duration' => [
                    'seconds' => (int) $this->duration_seconds_per_day,
                    'minutes' => (int) floor(((int) $this->duration_seconds_per_day) / 60),
                    'label' => $this->durationLabel((int) $this->duration_seconds_per_day),
                ],

                'repeat_pattern' => $this->repeat_pattern->value ?? "لايوجد تكرار",
                'repeat_weekday' => $this->repeat_pattern === RepeatPattern::None
                    ? null
                    : $this->repeatWeekdayName($this->repeat_weekday),

                'reminder' => [
                    'offset_minutes' => $this->reminder_offset_minutes !== null
                        ? (int) $this->reminder_offset_minutes
                        : null,
                    'label' => $this->reminderLabel($this->reminder_offset_minutes),
                ],
            ],

            'subtasks' => $this->studyTaskSubtasks
                ->sortBy('position')
                ->values()
                ->map(fn ($subtask) => [
                    'id' => (int) $subtask->id,
                    'title' => $subtask->title,
                    'is_completed' => (bool) $subtask->is_completed,
                ]),
        ];
    }

    private function repeatWeekdayName(null|int|string $repeatWeekday): ?string
    {
        if ($repeatWeekday === null) {
            return null;
        }

        return match ((int) $repeatWeekday) {
            0 => 'الأحد',
            1 => 'الاثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
            default => null,
        };
    }

    private function formatTime(mixed $time): ?string
    {
        if ($time === null) {
            return null;
        }

        return substr((string) $time, 0, 5);
    }


    private function reminderLabel(null|int|string $offsetMinutes): string
    {
        if ($offsetMinutes === null) {
            return 'لا يوجد';
        }

        $minutes = (int) $offsetMinutes;

        return match ($minutes) {
            0 => 'وقت المهمة',
            5 => '5 دقائق قبل المهمة',
            15 => '15 دقيقة قبل المهمة',
            30 => '30 دقيقة قبل المهمة',
            45 => '45 دقيقة قبل المهمة',
            60 => 'ساعة قبل المهمة',
            120 => 'ساعتان قبل المهمة',
            240 => '4 ساعات قبل المهمة',
            720 => '12 ساعة قبل المهمة',
            1440 => 'يوم قبل المهمة',
            2880 => 'يومان قبل المهمة',
            10080 => '7 أيام قبل المهمة',
            default => "{$minutes} دقيقة قبل المهمة",
        };
    }

    private function durationLabel(int $durationSeconds): string
    {
        $minutes = (int) floor($durationSeconds / 60);

        if ($minutes < 60) {
            return "{$minutes} دقيقة";
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return $hours === 1 ? 'ساعة واحدة' : "{$hours} ساعات";
        }

        return "{$hours} ساعة و {$remainingMinutes} دقيقة";
    }
}
