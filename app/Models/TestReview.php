<?php

namespace App\Models;

use App\Models\Test;
use App\Models\TestReviewFeedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestReview extends Model
{
    protected $table = 'test_reviews';

    protected $fillable = [
        'test_id',
        'user_id',
        'rating',
        'review_text',
        'helpful_yes_count',
        'helpful_no_count',
    ];

    protected $casts = [
        'helpful_yes_count' => 'integer',
        'helpful_no_count' => 'integer',
        'rating'=> 'integer',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'test_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function testReviewFeedback(): hasOne
    {
        return $this->hasOne(TestReviewFeedback::class, 'test_review_id');
    }

    public function feedbacks(): TestReview|\Illuminate\Database\Eloquent\Builder|HasMany
    {
        return $this->hasMany(TestReviewFeedback::class, 'test_review_id');
    }
}
