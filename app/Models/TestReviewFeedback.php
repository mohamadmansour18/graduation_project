<?php

namespace App\Models;

use App\Enums\Vote;
use App\Models\TestReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestReviewFeedback extends Model
{

    protected $table = 'test_review_feedbacks';

    protected $fillable = [
        'test_review_id',
        'user_id',
        'vote',
    ];

    protected $casts = [
        'vote' => Vote::class
    ];

    public function testReview(): BelongsTo
    {
        return $this->belongsTo(TestReview::class, 'test_review_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
