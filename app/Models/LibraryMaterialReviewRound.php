<?php

namespace App\Models;

use App\Enums\LibraryDecision;
use App\Enums\LibraryTriggerType;
use App\Models\LibraryMaterial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LibraryMaterialReviewRound extends Model
{
    use HasFactory;

    protected $table = 'library_material_review_rounds';

    protected $fillable = [
        'library_material_id',
        'round_no',
        'reviewer_user_id',
        'trigger_type',
        'decision',
        'based_on_approval_version',
        'started_at',
        'decided_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'decided_at' => 'datetime',
        'trigger_type' => LibraryTriggerType::class,
        'decision' => LibraryDecision::class,
    ];

    public function libraryMaterial(): BelongsTo
    {
        return $this->belongsTo(LibraryMaterial::class, 'library_material_id');
    }

    public function reviewerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
