<?php

namespace App\Models;

use App\Models\StudyPlanSubject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudySubject extends Model
{

    protected $table = 'study_subject';

    protected $fillable = [
        'user_id',
        'name',
    ];

    protected $casts = [
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function studyPlanSubjects(): HasMany
    {
        return $this->hasMany(StudyPlanSubject::class, 'study_subject_id');
    }
}
