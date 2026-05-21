<?php

namespace App\Models;

use App\Enums\TestReviewStatus;
use App\Models\Test;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestStatusHistory extends Model
{

    protected $table = 'test_status_histories';

    protected $fillable = [
        'test_id',
        'test_review_round_id',
        'from_status',
        'to_status',
        'changed_by_user_id',
        'note',
    ];

    protected $casts = [
        'from_status' => TestReviewStatus::class,
        'to_status' => TestReviewStatus::class,
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function reviewRound(): BelongsTo
    {
        return $this->belongsTo(TestReviewRound::class, 'test_review_round_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
