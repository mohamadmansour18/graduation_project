<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdminYearlyLibraryMaterialActivityMonthStat extends Model
{
    use HasFactory;

    protected $table = 'admin_yearly_library_material_activity_month_stats';

    protected $fillable = [
        'year',
        'month_no',
        'published_materials_count',
        'likes_count',
    ];

    protected $casts = [
        'year' => 'integer',
        'month_no' => 'integer',
        'published_materials_count' => 'integer',
        'likes_count' => 'integer',
    ];
}
