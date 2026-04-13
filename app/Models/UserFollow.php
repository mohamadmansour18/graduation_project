<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserFollow extends Model
{
    protected $table = 'user_follows';

    protected $fillable = [
        'follower_user_id',
        'followed_user_id',
    ];

    protected $casts = [
    ];

    public function followerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_user_id');
    }

    public function followedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'followed_user_id');
    }
}
