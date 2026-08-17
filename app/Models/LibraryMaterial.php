<?php

namespace App\Models;

use App\Enums\LibraryMaterialContentKind;
use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\TargetLevel;
use App\Enums\VisibilityType;
use App\Helpers\ArabicSearchNormalizer;
use App\Models\LibraryMaterialAsset;
use App\Models\LibraryMaterialBookmark;
use App\Models\LibraryMaterialDownloadLog;
use App\Models\LibraryMaterialInterestSelection;
use App\Models\LibraryMaterialLike;
use App\Models\LibraryMaterialReport;
use App\Models\LibraryReportReasonCounter;
use App\Models\LibraryMaterialReviewRound;
use App\Models\LibraryMaterialStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

class LibraryMaterial extends Model
{
    use Searchable;

    protected $table = 'library_material';

    protected $fillable = [
        'creator_user_id',
        'title',
        'description',
        'content_kind',
        'visibility_type',
        'target_level',
        'review_status',
        'current_approval_version',
        'published_at',
        'asset_count',
        'like_count',
        'bookmarks_count',
        'download_count',
    ];

    protected $casts = [
        'content_kind' => LibraryMaterialContentKind::class,
        'visibility_type' => VisibilityType::class,
        'review_status' => LibraryMaterialReviewStatus::class,
        'target_level' => TargetLevel::class,
        'current_approval_version' => 'integer',
        'published_at' => 'datetime',
        'asset_count' => 'integer',
        'like_count' => 'integer',
        'bookmarks_count' => 'integer',
        'download_count' => 'integer',
    ];

    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function libraryMaterialAssets(): HasMany
    {
        return $this->hasMany(LibraryMaterialAsset::class, 'library_material_id');
    }

    public function libraryMaterialInterestSelections(): HasMany
    {
        return $this->hasMany(LibraryMaterialInterestSelection::class, 'library_material_id');
    }

    public function libraryMaterialReviewRounds(): HasMany
    {
        return $this->hasMany(LibraryMaterialReviewRound::class, 'library_material_id');
    }

    public function libraryMaterialStatusHistories(): HasMany
    {
        return $this->hasMany(LibraryMaterialStatusHistory::class, 'library_material_id');
    }

    public function libraryMaterialBookmarks(): HasMany
    {
        return $this->hasMany(LibraryMaterialBookmark::class, 'library_material_id');
    }

    public function libraryMaterialLikes(): HasMany
    {
        return $this->hasMany(LibraryMaterialLike::class, 'library_material_id');
    }

    public function libraryMaterialDownloadLogs(): HasMany
    {
        return $this->hasMany(LibraryMaterialDownloadLog::class, 'library_material_id');
    }

    public function libraryMaterialReports(): HasMany
    {
        return $this->hasMany(LibraryMaterialReport::class, 'library_material_id');
    }

    public function libraryMaterialReportReasonCounters(): HasMany
    {
        return $this->hasMany(LibraryReportReasonCounter::class, 'library_material_id');
    }

    public function firstAsset(): HasOne|\Illuminate\Database\Eloquent\Builder|LibraryMaterial
    {
        return $this->hasOne(LibraryMaterialAsset::class, 'library_material_id')
            ->oldest('position')
            ->oldest('id');
    }

    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(
            Interest::class,
            'library_material_interest_selections',
            'library_material_id',
            'interest_id'
        )->withPivot('slot_no')->orderBy('library_material_interest_selections.slot_no');
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'normalized_title' => ArabicSearchNormalizer::normalize($this->title),
            'creator_user_id' => $this->creator_user_id,
            'content_kind' => $this->content_kind,
            'review_status' => $this->review_status,
            'visibility_type' => $this->visibility_type,
            'published_at_timestamp' => optional($this->published_at)->timestamp,
            'interest_ids' => $this->libraryMaterialInterestSelections()
                ->pluck('interest_id')
                ->map(fn ($id) => (int) $id)
                ->all(),
            'like_count' => (int) ($this->like_count ?? 0),
        ];
    }
}
