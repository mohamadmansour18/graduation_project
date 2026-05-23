<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\SystemRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, Billable;


    protected $table = 'users';

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'email_verified_at',
        'onboarding_completed_at',
        'last_login_at',
        'gender',
        'is_academically_verified',
        'academically_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
        'last_login_at' => 'datetime',
        'gender' => Gender::class,
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function authOtpCodes(): HasMany
    {
        return $this->hasMany(AuthOtpCode::class, 'user_id');
    }

    public function userBans(): HasMany
    {
        return $this->hasMany(UserBan::class, 'user_id');
    }

    public function imposedByUserBans(): HasMany
    {
        return $this->hasMany(UserBan::class, 'imposed_by_user_id');
    }

    public function liftedByUserBans(): HasMany
    {
        return $this->hasMany(UserBan::class, 'lifted_by_user_id');
    }

    public function userOnboardingProfile(): HasOne
    {
        return $this->hasOne(UserOnboardingProfile::class, 'user_id');
    }

    public function userUniversityProfile(): HasOne
    {
        return $this->hasOne(UserUniversityProfile::class, 'user_id');
    }

    public function userSchoolProfile(): HasOne
    {
        return $this->hasOne(UserSchoolProfile::class, 'user_id');
    }

    public function userInterestSelections(): HasMany
    {
        return $this->hasMany(UserInterestSelection::class, 'user_id');
    }

    public function creatorTests(): HasMany
    {
        return $this->hasMany(Test::class, 'creator_user_id');
    }

    public function buyerTestPurchases(): HasMany
    {
        return $this->hasMany(TestPurchase::class, 'buyer_user_id');
    }

    public function sellerTestPurchases(): HasMany
    {
        return $this->hasMany(TestPurchase::class, 'seller_user_id');
    }

    public function testAttempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class, 'user_id');
    }

    public function testReports(): HasMany
    {
        return $this->hasMany(TestReport::class, 'user_id');
    }

    public function reviewerTestReviewRounds(): HasMany
    {
        return $this->hasMany(TestReviewRound::class, 'reviewer_user_id');
    }

    public function createdByTestRevisionRequests(): HasMany
    {
        return $this->hasMany(TestRevisionRequest::class, 'created_by_user_id');
    }

    public function changedByTestRevisionChangeLogs(): HasMany
    {
        return $this->hasMany(TestRevisionChangeLog::class, 'changed_by_user_id');
    }

    public function changedByTestStatusHistories(): HasMany
    {
        return $this->hasMany(TestStatusHistory::class, 'changed_by_user_id');
    }

    public function testBookmarks(): HasMany
    {
        return $this->hasMany(TestBookmark::class, 'user_id');
    }

    public function testLikes(): HasMany
    {
        return $this->hasMany(TestLike::class, 'user_id');
    }

    public function testReviews(): HasMany
    {
        return $this->hasMany(TestReview::class, 'user_id');
    }

    public function testReviewFeedbacks(): HasMany
    {
        return $this->hasMany(TestReviewFeedback::class, 'user_id');
    }

    public function testDownloadLogs(): HasMany
    {
        return $this->hasMany(TestDownloadLog::class, 'user_id');
    }

    public function creatorLibraryMaterials(): HasMany
    {
        return $this->hasMany(LibraryMaterial::class, 'creator_user_id');
    }

    public function imposedByLibraryMaterials(): HasMany
    {
        return $this->hasMany(LibraryMaterial::class, 'imposed_by_user_id');
    }

    public function reviewerLibraryMaterialReviewRounds(): HasMany
    {
        return $this->hasMany(LibraryMaterialReviewRound::class, 'reviewer_user_id');
    }

    public function changedByLibraryMaterialStatusHistories(): HasMany
    {
        return $this->hasMany(LibraryMaterialStatusHistory::class, 'changed_by_user_id');
    }

    public function libraryMaterialBookmarks(): HasMany
    {
        return $this->hasMany(LibraryMaterialBookmark::class, 'user_id');
    }

    public function libraryMaterialLikes(): HasMany
    {
        return $this->hasMany(LibraryMaterialLike::class, 'user_id');
    }

    public function libraryMaterialDownloadLogs(): HasMany
    {
        return $this->hasMany(LibraryMaterialDownloadLog::class, 'user_id');
    }

    public function libraryMaterialReports(): HasMany
    {
        return $this->hasMany(LibraryMaterialReport::class, 'user_id');
    }

    public function creatorTestFolders(): HasMany
    {
        return $this->hasMany(TestFolder::class, 'creator_user_id');
    }

    public function testFolderBookmarks(): HasMany
    {
        return $this->hasMany(TestFolderBookmark::class, 'user_id');
    }

    public function studySubjects(): HasMany
    {
        return $this->hasMany(StudySubject::class, 'user_id');
    }

    public function studyPlans(): HasMany
    {
        return $this->hasMany(StudyPlan::class, 'user_id');
    }

    public function userSetting(): HasOne
    {
        return $this->hasOne(UserSetting::class, 'user_id');
    }

    public function userAcademicVerificationRequests(): HasMany
    {
        return $this->hasMany(UserAcademicVerificationRequest::class, 'user_id');
    }

    public function reviewerUserAcademicVerificationRequests(): HasMany
    {
        return $this->hasMany(UserAcademicVerificationRequest::class, 'reviewer_user_id');
    }

    public function userYearlyStudyStats(): HasMany
    {
        return $this->hasMany(UserYearlyStudyStat::class, 'user_id');
    }

    public function userYearlyStudyPlanStats(): HasMany
    {
        return $this->hasMany(UserYearlyStudyPlanStat::class, 'user_id');
    }

    public function userYearlyTestStats(): HasMany
    {
        return $this->hasMany(UserYearlyTestStat::class, 'user_id');
    }

    public function userYearlyTestPublishMonthStats(): HasMany
    {
        return $this->hasMany(UserYearlyTestPublishMonthStat::class, 'user_id');
    }

    public function userProfile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    public function followerUserFollows(): HasMany
    {
        return $this->hasMany(UserFollow::class, 'follower_user_id');
    }

    public function followedUserFollows(): HasMany
    {
        return $this->hasMany(UserFollow::class, 'followed_user_id');
    }

    public function userProfileStat(): HasOne
    {
        return $this->hasOne(UserProfileStat::class, 'user_id');
    }

    public function isOwner(): bool
    {
        return $this->role?->name === SystemRole::Owner->vale;
    }

    public function isSupervisor(): bool
    {
        return $this->role?->name === SystemRole::Supervisor;
    }

    public function isMobileUser(): bool
    {
        return $this->role?->name === SystemRole::Mobile_User;
    }

    public function isDashboardUser():bool
    {
        return in_array($this->role?->name , [SystemRole::Owner , SystemRole::Supervisor] , true);
    }

    public function failedLogin(): hasOne
    {
        return $this->hasOne(FailedLogin::class , 'user_id' );
    }

    public function aiQuestionGenerationRequests(): HasMany
    {
        return $this->hasMany(AiQuestionGenerationRequest::class , 'user_id');
    }
}
