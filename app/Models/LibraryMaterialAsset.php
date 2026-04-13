<?php

namespace App\Models;

use App\Enums\Asset_type;
use App\Models\LibraryMaterial;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LibraryMaterialAsset extends Model
{

    protected $table = 'library_material_asset';

    protected $fillable = [
        'library_material_id',
        'asset_type',
        'storage_disk',
        'storage_path',
        'original_name',
        'mime_type',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
        'asset_type' => Asset_type::class,
    ];

    public function libraryMaterial(): BelongsTo
    {
        return $this->belongsTo(LibraryMaterial::class, 'library_material_id');
    }
}
