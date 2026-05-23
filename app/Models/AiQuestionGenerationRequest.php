<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiQuestionGenerationRequest extends Model
{
    protected $fillable = [
        'user_id',
        'source_type',
        'status',
        'requested_question_count',
        'difficulty_level',
        'language',
        'provider',
        'model',
        'generated_questions_json',
        'failure_code',
        'failure_message',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'requested_question_count' => 'integer',
        'generated_questions_json' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class , 'user_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(
            AiQuestionGenerationAsset::class,
            'ai_question_generation_id' , 'id'
        );
    }

    public function isCompleted(): bool
    {
        return $this->status === 'Completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'Failed';
    }
}
