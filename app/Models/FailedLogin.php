<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FailedLogin extends Model
{
    protected $table = 'failed_logins';

    protected $fillable = [
        'user_id',
        'email',
        'attempts_count',
        'window_started_at',
        'last_attempt_at',
        'last_notified_at',
        'last_user_agent',
        'last_ip_address'
    ];

    protected $casts = [
        'window_started_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'last_notified_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class , 'user_id' , 'id');
    }
}
