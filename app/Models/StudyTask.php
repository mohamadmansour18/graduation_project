<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\RepeatPattern;
use App\Enums\StudyTaskStatus;
use App\Enums\TaskStatus;
use App\Models\StudyPlan;
use App\Models\StudyPlanSubject;
use App\Models\StudyTaskOccurrence;
use App\Models\StudyTaskSubtask;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudyTask extends Model
{

    protected $table = 'study_task';

    protected $fillable = [
        'study_plan_id',
        'study_plan_subject_id',
        'task_group_uuid',
        'title',
        'description',
        'start_date',
        'end_date',
        'start_time',
        'duration_seconds_per_day',
        'deadline_at',
        'reminder_offset_minutes',
        'priority',
        'status',
        'completed_at',
        'missed_at',
        'repeat_pattern',
        'recurrence_end_date',
        'repeat_weekday',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_minutes_per_day' => 'integer',
        'deadline_at' => 'datetime',
        'reminder_offset_minutes' => 'integer',
        'status' => TaskStatus::class,
        'completed_at' => 'datetime',
        'missed_at' => 'datetime',
        'repeat_pattern' => RepeatPattern::class,
        'recurrence_end_date' => 'date',
        'priority' => Priority::class,
        'repeat_weekday' => 'integer',
    ];

    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class, 'study_plan_id');
    }

    public function studyPlanSubject(): BelongsTo
    {
        return $this->belongsTo(StudyPlanSubject::class, 'study_plan_subject_id');
    }

    public function studyTaskOccurrences(): HasMany
    {
        return $this->hasMany(StudyTaskOccurrence::class, 'study_task_id');
    }

    public function studyTaskSubtasks(): HasMany
    {
        return $this->hasMany(StudyTaskSubtask::class, 'study_task_id');
    }
}
