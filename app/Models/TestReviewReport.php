<?php

namespace App\Models;

use App\Enums\TestReviewReportReason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestReviewReport extends Model
{
    protected $fillable = [
        'test_review_id',
        'user_id',
        'reason',
        'description',
        'reported_at',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'reason' => TestReviewReportReason::class,
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(TestReview::class, 'test_review_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class , 'user_id');
    }
}
