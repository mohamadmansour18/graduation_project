<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdminYearlyTestActivityMonthStat extends Model
{

    protected $table = 'admin_yearly_test_activity_month_stats';

    protected $fillable = [
        'year',
        'month_no',
        'published_tests_count',
        'likes_count',
        'reviews_count',
        'downloads_count',
    ];

    protected $casts = [
        'year' => 'integer',
        'month_no' => 'integer',
        'published_tests_count' => 'integer',
        'likes_count' => 'integer',
        'reviews_count' => 'integer',
        'downloads_count' => 'integer',
    ];
}
