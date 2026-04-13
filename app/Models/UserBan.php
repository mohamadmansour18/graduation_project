<?php

namespace App\Models;

use App\Enums\BanType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserBan extends Model
{

    protected $table = 'user_bans';

    protected $fillable = [
        'user_id',
        'imposed_by_user_id',
        'ban_type',
        'reason',
        'starts_at',
        'ends_at',
        'lifted_by_user_id',
        'lifted_at',
    ];

    protected $casts = [
        'ban_type' => BanType::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'lifted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function imposedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imposed_by_user_id');
    }

    public function liftedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lifted_by_user_id');
    }
}
