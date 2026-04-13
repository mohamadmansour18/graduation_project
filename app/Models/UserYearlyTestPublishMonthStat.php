<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserYearlyTestPublishMonthStat extends Model
{

    protected $table = 'user_yearly_test_publish_month_stats';

    protected $fillable = [
        'user_id',
        'year',
        'month_no',
        'published_tests_count',
    ];

    protected $casts = [
        'year' => 'integer',
        'month_no' => 'integer',
        'published_tests_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
