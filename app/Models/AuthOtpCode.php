<?php

namespace App\Models;

use App\Enums\PurposeOTP;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AuthOtpCode extends Model
{

    protected $table = 'auth_otp_codes';

    protected $fillable = [
        'user_id',
        'purpose',
        'code_hash',
        'send_to_email',
        'expires_at',
        'consumed_at',
        'revoked_at',
        'attempts_count',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'revoked_at' => 'datetime',
        'attempts_count' => 'integer',
        'purpose' => PurposeOTP::class
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
