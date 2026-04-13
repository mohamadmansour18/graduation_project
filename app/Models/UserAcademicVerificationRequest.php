<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\User;
use App\Models\UserAcademicAsset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserAcademicVerificationRequest extends Model
{
    use HasFactory;

    protected $table = 'user_academic_verification_requests';

    protected $fillable = [
        'user_id',
        'status',
        'submitted_at',
        'reviewer_user_id',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'status'=> Status::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function verificationRequestUserAcademicVerificationAssets(): HasMany
    {
        return $this->hasMany(UserAcademicAsset::class, 'verification_request_id');
    }
}
