<?php

namespace App\Models;

use App\Models\StudyTask;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudyTaskSubtask extends Model
{

    protected $table = 'study_task_subtask';

    protected $fillable = [
        'study_task_id',
        'title',
        'position',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function studyTask(): BelongsTo
    {
        return $this->belongsTo(StudyTask::class, 'study_task_id');
    }
}
