<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserStatsSummary extends Model
{
    protected $table = 'user_stats_summary';

    protected $fillable = [
        'year',
        'total_completed_mobile_users',
        'male_completed_mobile_users',
        'female_completed_mobile_users',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_completed_mobile_users' => 'integer',
        'male_completed_mobile_users' => 'integer',
        'female_completed_mobile_users' => 'integer',
    ];
}
