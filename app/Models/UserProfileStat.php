<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserProfileStat extends Model
{

    protected $table = 'user_profile_stats';

    protected $fillable = [
        'user_id',
        'followers_count',
        'following_count',
        'published_tests_count',
        'library_materials_count',
        'folders_count',
        'average_test_rating',
        'total_test_likes_received',
        'total_test_reviews_received',
        'total_test_bookmarks_received',
    ];

    protected $casts = [
        'followers_count' => 'integer',
        'following_count' => 'integer',
        'published_tests_count' => 'integer',
        'library_materials_count' => 'integer',
        'folders_count' => 'integer',
        'average_test_rating' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
