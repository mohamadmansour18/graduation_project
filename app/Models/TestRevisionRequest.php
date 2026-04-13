<?php

namespace App\Models;

use App\Enums\Decision;
use App\Enums\RevisionType;
use App\Models\Test;
use App\Models\TestQuestion;
use App\Models\TestReviewRound;
use App\Models\TestRevisionChangeLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestRevisionRequest extends Model
{

    protected $table = 'test_revision_requests';

    protected $fillable = [
        'test_review_round_id',
        'test_id',
        'revision_type',
        'target_question_id',
        'decision',
        'created_by_user_id',
        'resolved_at',
        'problem_note',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'revision_type' => RevisionType::class,
        'decision' => Decision::class,
    ];

    public function testReviewRound(): BelongsTo
    {
        return $this->belongsTo(TestReviewRound::class, 'test_review_round_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function targetQuestion(): BelongsTo
    {
        return $this->belongsTo(TestQuestion::class, 'target_question_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function revisionRequestTestRevisionChangeLogs(): HasMany
    {
        return $this->hasMany(TestRevisionChangeLog::class, 'revision_request_id');
    }
}
