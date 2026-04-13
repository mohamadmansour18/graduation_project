<?php

namespace App\Models;

use App\Enums\LibraryMaterialReviewStatus;
use App\Models\LibraryMaterial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LibraryMaterialStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'library_material_status_histories';

    protected $fillable = [
        'library_material_id',
        'from_status',
        'to_status',
        'changed_by_user_id',
        'note',
    ];

    protected $casts = [
        'from_status' => LibraryMaterialReviewStatus::class,
        'to_status' => LibraryMaterialReviewStatus::class,
    ];

    public function libraryMaterial(): BelongsTo
    {
        return $this->belongsTo(LibraryMaterial::class, 'library_material_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
