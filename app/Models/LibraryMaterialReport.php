<?php

namespace App\Models;

use App\Enums\LibraryReportReason;
use App\Models\LibraryMaterial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LibraryMaterialReport extends Model
{
    protected $table = 'library_material_reports';

    protected $fillable = [
        'library_material_id',
        'user_id',
        'approval_version',
        'reason',
        'description',
        'reported_at',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'reason' => LibraryReportReason::class,
    ];

    public function libraryMaterial(): BelongsTo
    {
        return $this->belongsTo(LibraryMaterial::class, 'library_material_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
