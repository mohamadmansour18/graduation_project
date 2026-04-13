<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserYearlyTestStat extends Model
{
    use HasFactory;

    protected $table = 'user_yearly_test_stats';

    protected $fillable = [
        'user_id',
        'year',
        'total_likes_received',
        'total_reviews_received',
        'total_bookmarks_received',
        'published_tests_count',
        'first_published_test_at',
        'last_published_test_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'published_tests_count' => 'integer',
        'first_published_test_at' => 'datetime',
        'last_published_test_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
