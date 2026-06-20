<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestAiEvaluationRequest extends Model
{
    protected $fillable = [
        'test_id',
        'requested_by_user_id',
        'status',
        'content_hash',
        'questions_count',
        'input_questions_json',
        'provider',
        'model',
        'score_percentage',
        'correct_questions_label',
        'suspicious_questions_label',
        'issues_json',
        'raw_response_json',
        'failure_code',
        'failure_message',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'questions_count' => 'integer',
        'input_questions_json' => 'array',
        'score_percentage' => 'integer',
        'issues_json' => 'array',
        'raw_response_json' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
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
