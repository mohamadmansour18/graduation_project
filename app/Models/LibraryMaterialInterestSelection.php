<?php

namespace App\Models;

use App\Models\Interest;
use App\Models\LibraryMaterial;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LibraryMaterialInterestSelection extends Model
{

    protected $table = 'library_material_interest_selections';

    protected $fillable = [
        'library_material_id',
        'interest_id',
        'slot_no',
    ];

    protected $casts = [
        'slot_no' => 'integer',
    ];

    public function libraryMaterial(): BelongsTo
    {
        return $this->belongsTo(LibraryMaterial::class, 'library_material_id');
    }

    public function interest(): BelongsTo
    {
        return $this->belongsTo(Interest::class, 'interest_id');
    }
}
