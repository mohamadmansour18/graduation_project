<?php

namespace App\Models;

use App\Enums\DiscoverySource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserStatsByDiscoverySource extends Model
{

    protected $table = 'user_stats_by_discovery_source';

    protected $fillable = [
        'year',
        'discovery_source',
        'completed_mobile_users_count',
    ];

    protected $casts = [
        'year' => 'integer',
        'discovery_source' => DiscoverySource::class,
        'completed_mobile_users_count' => 'integer',
    ];
}
