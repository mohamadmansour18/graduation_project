<?php

namespace App\Models;

use App\Enums\Decision;
use App\Enums\TestReviewRoundsTriggerType;
use App\Models\Test;
use App\Models\TestRevisionChangeLog;
use App\Models\TestRevisionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestReviewRound extends Model
{
    protected $table = 'test_review_rounds';

    protected $fillable = [
        'test_id',
        'round_no',
        'reviewer_user_id',
        'trigger_type',
        'decision',
        'based_on_approval_version',
        'started_at',
        'decided_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'decided_at' => 'datetime',
        'round_no' => 'integer',
        'based_on_approval_version' => 'integer',
        'trigger_type' => TestReviewRoundsTriggerType::class,
        'decision' => Decision::class,
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function reviewerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function testRevisionRequests(): HasMany
    {
        return $this->hasMany(TestRevisionRequest::class, 'test_review_round_id');
    }

    public function testRevisionChangeLogs(): HasMany
    {
        return $this->hasMany(TestRevisionChangeLog::class, 'test_review_round_id');
    }
}
