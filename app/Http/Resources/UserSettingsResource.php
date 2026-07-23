<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'task_reminders_enabled' => (bool) $this->task_reminders_enabled ?? 'null',
            'week_starts_on' => $this->week_starts_on ?? 'null',
            'time_format' => $this->time_format ?? 'null',
            'theme_mode' => $this->theme_mode ?? 'null',
        ];
    }
}
