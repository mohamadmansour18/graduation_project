<?php

namespace App\Models;

use App\Models\StudyPlan;
use App\Models\StudySubject;
use App\Models\StudyTask;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudyPlanSubject extends Model
{

    protected $table = 'study_plan_subject';

    protected $fillable = [
        'study_plan_id',
        'study_subject_id',
        'slot_no',
    ];

    protected $casts = [
        'slot_no' => 'integer',
    ];

    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class, 'study_plan_id');
    }

    public function studySubject(): BelongsTo
    {
        return $this->belongsTo(StudySubject::class, 'study_subject_id');
    }

    public function studyTasks(): HasMany
    {
        return $this->hasMany(StudyTask::class, 'study_plan_subject_id');
    }
}
