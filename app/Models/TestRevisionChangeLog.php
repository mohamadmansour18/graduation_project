<?php

namespace App\Models;

use App\Enums\RevisionType;
use App\Models\Test;
use App\Models\TestReviewRound;
use App\Models\TestRevisionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestRevisionChangeLog extends Model
{
    protected $table = 'test_revision_change_logs';

    protected $fillable = [
        'test_review_round_id',
        'test_id',
        'revision_request_id',
        'revision_type',
        'target_question_id',
        'before_value',
        'after_value',
        'changed_by_user_id',
        'target_option_id'
    ];

    protected $casts = [
        'revision_type' => RevisionType::class,
    ];

    public function testReviewRound(): BelongsTo
    {
        return $this->belongsTo(TestReviewRound::class, 'test_review_round_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function revisionRequest(): BelongsTo
    {
        return $this->belongsTo(TestRevisionRequest::class, 'revision_request_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    public function targetQuestion(): BelongsTo
    {
        return $this->belongsTo(TestQuestion::class, 'target_question_id');
    }

    public function targetOption(): BelongsTo
    {
        return $this->belongsTo(TestQuestionOption::class, 'target_option_id');
    }
}
