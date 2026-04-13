<?php

namespace App\Models;

use App\Models\LibraryMaterial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LibraryMaterialDownloadLog extends Model
{
    use HasFactory;

    protected $table = 'library_material_download_logs';

    protected $fillable = [
        'library_material_id',
        'user_id',
    ];

    protected $casts = [
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
