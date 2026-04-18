<?php

namespace App\Models;

use App\Enums\AcademicAssetType;
use App\Models\UserAcademicVerificationRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserAcademicAsset extends Model
{

    protected $table = 'user_academic_assets';

    protected $fillable = [
        'verification_request_id',
        'asset_type',
        'storage_disk',
        'storage_path',
        'original_name',
        'mime_type',
        'file_size_bytes',
    ];

    protected $casts = [
        'asset_type' => AcademicAssetType::class,
    ];

    public function verificationRequest(): BelongsTo
    {
        return $this->belongsTo(UserAcademicVerificationRequest::class, 'verification_request_id');
    }
}
