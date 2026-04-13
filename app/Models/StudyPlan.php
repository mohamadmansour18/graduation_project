<?php

namespace App\Models;

use App\Models\StudyPlanSubject;
use App\Models\StudyTask;
use App\Models\StudyTaskOccurrence;
use App\Models\User;
use App\Models\UserYearlyStudyPlanStat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudyPlan extends Model
{

    protected $table = 'study_plan';

    protected $fillable = [
        'user_id',
        'title',
        'emoji',
        'start_date',
        'end_date',
        'daily_study_minutes',
        'is_default',
        'subjects_count',
        'tasks_count',
        'completed_tasks_count',
        'missed_tasks_count',
        'pending_tasks_count',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'daily_study_minutes' => 'integer',
        'is_default' => 'boolean',
        'subjects_count' => 'integer',
        'tasks_count' => 'integer',
        'completed_tasks_count' => 'integer',
        'missed_tasks_count' => 'integer',
        'pending_tasks_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function studyPlanSubjects(): HasMany
    {
        return $this->hasMany(StudyPlanSubject::class, 'study_plan_id');
    }

    public function studyTasks(): HasMany
    {
        return $this->hasMany(StudyTask::class, 'study_plan_id');
    }

    public function studyTaskOccurrences(): HasMany
    {
        return $this->hasMany(StudyTaskOccurrence::class, 'study_plan_id');
    }

    public function userYearlyStudyPlanStats(): HasMany
    {
        return $this->hasMany(UserYearlyStudyPlanStat::class, 'study_plan_id');
    }
}
