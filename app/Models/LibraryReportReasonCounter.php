<?php

namespace App\Models;

use App\Enums\LibraryReportReason;
use App\Models\LibraryMaterial;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LibraryReportReasonCounter extends Model
{

    protected $table = 'library_report_reason_counters';

    protected $fillable = [
        'library_material_id',
        'approval_version',
        'reason',
        'reporters_count',
    ];

    protected $casts = [
        'reporters_count' => 'integer',
        'reason' => LibraryReportReason::class,
    ];

    public function libraryMaterial(): BelongsTo
    {
        return $this->belongsTo(LibraryMaterial::class, 'library_material_id');
    }
}
