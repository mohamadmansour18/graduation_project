<?php

namespace App\Models;

use App\Models\StudyPlan;
use App\Models\StudyTask;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudyTaskOccurrence extends Model
{
    use HasFactory;

    protected $table = 'study_task_occurrence';

    protected $fillable = [
        'study_task_id',
        'study_plan_id',
        'occurrence_date',
        'scheduled_start_time',
        'scheduled_end_time',
        'duration_second',
    ];

    protected $casts = [
        'occurrence_date' => 'date',
        'duration_second' => 'integer',
    ];

    public function studyTask(): BelongsTo
    {
        return $this->belongsTo(StudyTask::class, 'study_task_id');
    }

    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class, 'study_plan_id');
    }
}
