<?php

namespace App\Models;

use App\Enums\DifficultyLevel;
use App\Enums\Language;
use App\Enums\TargetLevel;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Models\AdminYearlyFinancialStat;
use App\Models\AdminYearlyTestSalesStat;
use App\Models\TestAttempt;
use App\Models\TestBookmark;
use App\Models\TestDownloadLog;
use App\Models\TestFolderItem;
use App\Models\TestIntersetSelection;
use App\Models\TestLike;
use App\Models\TestPurchase;
use App\Models\TestQuestion;
use App\Models\TestReport;
use App\Models\TestReview;
use App\Models\TestReviewRound;
use App\Models\TestRevisionChangeLog;
use App\Models\TestRevisionRequest;
use App\Models\TestStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Test extends Model
{
    protected $table = 'test';

    protected $fillable = [
        'creator_user_id',
        'title',
        'description',
        'test_type',
        'difficulty_level',
        'duration_seconds',
        'pass_mark_percentage',
        'language',
        'price',
        'target_level',
        'review_status',
        'current_approval_version',
        'published_at',
        'last_content_updated_at',
        'question_count',
        'preview_question_count',
        'likes_count',
        'bookmarks_count',
        'downloads_count',
        'reviews_count',
        'participants_count',
        'average_rating',
    ];

    protected $casts = [
        'test_type' => TestType::class,
        'duration_seconds' => 'integer',
        'price' => 'decimal:2',
        'review_status' => TestReviewStatus::class,
        'target_level' => TargetLevel::class,
        'language' => Language::class,
        'difficulty_level' => DifficultyLevel::class,
        'current_approval_version' => 'integer',
        'published_at' => 'datetime',
        'last_content_updated_at' => 'datetime',
        'question_count' => 'integer',
        'preview_question_count' => 'integer',
        'likes_count' => 'integer',
        'bookmarks_count' => 'integer',
        'downloads_count' => 'integer',
        'reviews_count' => 'integer',
        'participants_count' => 'integer',
        'average_rating' => 'decimal:2',
    ];

    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function testIntersetSelections(): HasMany
    {
        return $this->hasMany(TestIntersetSelection::class, 'test_id');
    }

    public function testQuestions(): HasMany
    {
        return $this->hasMany(TestQuestion::class, 'test_id');
    }

    public function testPurchases(): HasMany
    {
        return $this->hasMany(TestPurchase::class, 'test_id');
    }

    public function testAttempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class, 'test_id');
    }

    public function testReports(): HasMany
    {
        return $this->hasMany(TestReport::class, 'test_id');
    }

    public function testReviewRounds(): HasMany
    {
        return $this->hasMany(TestReviewRound::class, 'test_id');
    }

    public function testRevisionRequests(): HasMany
    {
        return $this->hasMany(TestRevisionRequest::class, 'test_id');
    }

    public function testRevisionChangeLogs(): HasMany
    {
        return $this->hasMany(TestRevisionChangeLog::class, 'test_id');
    }

    public function testStatusHistories(): HasMany
    {
        return $this->hasMany(TestStatusHistory::class, 'test_id');
    }

    public function testBookmarks(): HasMany
    {
        return $this->hasMany(TestBookmark::class, 'test_id');
    }

    public function testLikes(): HasMany
    {
        return $this->hasMany(TestLike::class, 'test_id');
    }

    public function testReviews(): HasMany
    {
        return $this->hasMany(TestReview::class, 'test_id');
    }

    public function testDownloadLogs(): HasMany
    {
        return $this->hasMany(TestDownloadLog::class, 'test_id');
    }

    public function testFolderItems(): HasMany
    {
        return $this->hasMany(TestFolderItem::class, 'test_id');
    }

    public function mostPurchasedAdminYearlyFinancialStats(): HasMany
    {
        return $this->hasMany(AdminYearlyFinancialStat::class, 'most_purchased_test_id');
    }

    public function adminYearlyTestSalesStats(): HasMany
    {
        return $this->hasMany(AdminYearlyTestSalesStat::class, 'test_id');
    }
}
