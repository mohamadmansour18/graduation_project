<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserProfile extends Model
{

    protected $table = 'user_profile';

    protected $fillable = [
        'user_id',
        'phone',
        'birth_date',
        'avatar_disk',
        'avatar_path',
        'cover_disk',
        'cover_path',
        'profile_slug',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
