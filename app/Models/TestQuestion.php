<?php

namespace App\Models;

use App\Models\Test;
use App\Models\TestQuestionOption;
use App\Models\TestRevisionRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestQuestion extends Model
{
    protected $table = 'test_question';

    protected $fillable = [
        'test_id',
        'position',
        'question_text',
        'hint_text',
        'is_preview',
        'options_count',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_preview' => 'boolean',
        'options_count' => 'integer',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function testQuestionOptions(): HasMany
    {
        return $this->hasMany(TestQuestionOption::class, 'test_question_id');
    }

    public function targetQuestionTestRevisionRequests(): HasMany
    {
        return $this->hasMany(TestRevisionRequest::class, 'target_question_id');
    }

    public function targetQuestionTestRevisionChangeLogs(): HasMany
    {
        return $this->hasMany(TestRevisionChangeLog::class, 'target_question_id');
    }
}
