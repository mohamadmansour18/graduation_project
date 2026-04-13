<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserYearlyStudyStat extends Model
{

    protected $table = 'user_yearly_study_stats';

    protected $fillable = [
        'user_id',
        'year',
        'total_tasks_count',
        'todo_tasks_count',
        'in_progress_tasks_count',
        'completed_tasks_count',
        'missed_tasks_count',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_tasks_count' => 'integer',
        'todo_tasks_count' => 'integer',
        'in_progress_tasks_count' => 'integer',
        'completed_tasks_count' => 'integer',
        'missed_tasks_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
