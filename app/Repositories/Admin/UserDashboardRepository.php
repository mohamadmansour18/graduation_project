<?php

namespace App\Repositories\Admin;

use App\Enums\BanType;
use App\Enums\Status;
use App\Enums\SystemRole;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Enums\VisibilityType;
use App\Models\LibraryMaterial;
use App\Models\Role;
use App\Models\Test;
use App\Models\TestFolder;
use App\Models\TestReview;
use App\Models\User;
use App\Models\UserAcademicAsset;
use App\Models\UserAcademicVerificationRequest;
use App\Models\UserBan;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\EnumeratesValues;

class UserDashboardRepository
{
    public function paginateUsersForDashboard(string $type, string $sortBy, int $perPage): CursorPaginator
    {
        $banStatusSubquery = DB::table('users as ban_users')
            ->select('ban_users.id as user_id')
            ->selectRaw("
            CASE
                WHEN EXISTS (
                    SELECT 1
                    FROM user_bans
                    WHERE user_bans.user_id = ban_users.id
                      AND user_bans.lifted_at IS NULL
                      AND (
                            user_bans.ends_at IS NULL
                            OR user_bans.ends_at > CURRENT_TIMESTAMP
                          )
                )
                THEN 1
                ELSE 0
            END AS is_banned
        ");

        $query = User::query()
            ->select([
                'users.id',
                'users.role_id',
                'users.name',
                'users.email',
                'users.gender',
                'users.last_login_at',
                'users.created_at',
                'user_profile.phone',
                'user_profile.avatar_disk',
                'user_profile.avatar_path',
                'user_profile.governorate',
                'ban_status.is_banned',
            ])
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->leftJoin('user_profile', 'user_profile.user_id', '=', 'users.id')
            ->joinSub($banStatusSubquery, 'ban_status', function ($join) {
                $join->on('ban_status.user_id', '=', 'users.id');
            });

        $this->applyUserTypeFilter($query, $type);
        $this->applySorting($query, $sortBy);

        return $query->cursorPaginate($perPage);
    }

    private function applyUserTypeFilter(Builder $query, string $type): void
    {
        match ($type) {
            'mobile_users' => $query
                ->where('roles.name', 'mobile_user')
                ->whereNotNull('users.email_verified_at')
                ->whereNotNull('users.onboarding_completed_at'),

            'supervisors' => $query->where('roles.name', 'supervisor'),

            'owners' => $query->where('roles.name', 'owner'),
        };
    }

    private function applySorting(Builder $query, string $sortBy): void
    {
        match ($sortBy) {
            'name' => $query
                ->orderBy('users.name')
                ->orderBy('users.id'),

            'governorate' => $query
                ->orderBy('user_profile.governorate')
                ->orderBy('users.id'),

            'gender' => $query
                ->orderByRaw("CASE users.gender WHEN 'انثى' THEN 0 ELSE 1 END")
                ->orderBy('users.id'),

            'account_status' => $query
                ->orderByDesc('ban_status.is_banned')
                ->orderBy('users.id'),

            default => $query
                ->orderByDesc('users.created_at')
                ->orderByDesc('users.id'),
        };
    }

    public function searchUsersByName(string $search, string $role, int $perPage): CursorPaginator
    {
        $safeSearch = addcslashes(trim($search), '%_\\');

        $query = User::query()
            ->select([
                'users.id',
                'users.role_id',
                'users.name',
                'users.email',
                'users.gender',
                'users.last_login_at',
                'users.created_at',
                'user_profile.phone',
                'user_profile.avatar_disk',
                'user_profile.avatar_path',
                'user_profile.governorate',
            ])
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->leftJoin('user_profile', 'user_profile.user_id', '=', 'users.id')
            ->withExists([
                'activeBan as is_banned',
            ])
            ->where('users.name', 'like', $safeSearch . '%');

        $this->applyUserTypeFilter($query, $role);

        return $query
            ->orderBy('users.name')
            ->orderBy('users.id')
            ->cursorPaginate($perPage);
    }

    public function findRoleByName(string $name): ?Role
    {
        return Role::query()
            ->select(['id', 'name'])
            ->where('name', $name)
            ->first();
    }

    public function createUser(array $data): User
    {
        return User::query()->create($data);
    }

    public function createUserProfile(User $user, array $data): UserProfile
    {
        return UserProfile::query()->create([
            'user_id' => $user->id,
            'phone' => $data['phone'],
            'governorate' => $data['governorate'],
        ]);
    }

    public function getActiveBannedUsers(string $tab = 'all'): array|Collection|\LaravelIdea\Helper\App\Models\_IH_UserBan_C
    {
        return UserBan::query()
            ->select([
                'user_bans.id',
                'user_bans.user_id',
                'user_bans.ban_type',
                'user_bans.starts_at',
                'user_bans.ends_at',
                'user_bans.created_at',
            ])
            ->with([
                'user:id,name,is_academically_verified',
                'user.userProfile:user_id,avatar_disk,avatar_path',
                'user.userOnboardingProfile:user_id,education_level',
            ])
            ->whereNull('user_bans.lifted_at')
            ->where('user_bans.starts_at', '<=', now())
            ->where(function ($query) {
                $query->where('user_bans.ban_type', BanType::Permanent->value)
                    ->orWhere(function ($query) {
                        $query->where('user_bans.ban_type', BanType::Temporary->value)
                            ->where('user_bans.ends_at', '>', now());
                    });
            })
            ->when($tab === 'permanent', function ($query) {
                $query->where('user_bans.ban_type', BanType::Permanent->value);
            })
            ->when($tab === 'temporary', function ($query) {
                $query->where('user_bans.ban_type', BanType::Temporary->value)
                    ->where('user_bans.ends_at', '>', now());
            })
            ->latest('user_bans.created_at')
            ->get();
    }

    public function getPendingAcademicVerificationRequests(string $sortBy = 'submitted_at'): Collection
    {
        $query = UserAcademicVerificationRequest::query()
            ->select([
                'user_academic_verification_requests.id',
                'user_academic_verification_requests.user_id',
                'user_academic_verification_requests.status',
                'user_academic_verification_requests.submitted_at',
            ])
            ->with([
                'user:id,name,email,gender',
                'user.userProfile:user_id,avatar_disk,avatar_path,governorate',
                'user.userUniversityProfile:user_id,university_name,department',
            ])
            ->where('user_academic_verification_requests.status', Status::PENDING->value);

        match ($sortBy) {
            'university' => $query
                ->join('user_university_profiles', 'user_university_profiles.user_id', '=', 'user_academic_verification_requests.user_id')
                ->orderBy('user_university_profiles.university_name')
                ->orderByDesc('user_academic_verification_requests.submitted_at'),

            'department' => $query
                ->join('user_university_profiles', 'user_university_profiles.user_id', '=', 'user_academic_verification_requests.user_id')
                ->orderBy('user_university_profiles.department')
                ->orderByDesc('user_academic_verification_requests.submitted_at'),

            'gender' => $query
                ->join('users', 'users.id', '=', 'user_academic_verification_requests.user_id')
                ->orderByRaw("CASE users.gender WHEN 'انثى' THEN 0 ELSE 1 END")
                ->orderByDesc('user_academic_verification_requests.submitted_at'),

            default => $query->orderByDesc('user_academic_verification_requests.submitted_at'),
        };

        return $query
            ->get();
    }

    public function findAcademicVerificationAsset(int $verificationRequestId, string $assetType): ?UserAcademicAsset
    {
        return UserAcademicAsset::query()
            ->select([
                'id',
                'verification_request_id',
                'asset_type',
                'storage_disk',
                'storage_path',
                'original_name',
                'mime_type',
            ])
            ->where('verification_request_id', $verificationRequestId)
            ->where('asset_type', $assetType)
            ->first();
    }

    public function getUserProfileDetails(int $userId): User
    {
        return User::query()
            ->select([
                'id',
                'name',
                'email',
                'gender',
                'is_academically_verified',
                'academically_verified_at',
                'created_at',
            ])
            ->with([
                'userProfile:user_id,avatar_path,cover_path,governorate,cover_disk,avatar_disk',
                'userProfileStat:user_id,followers_count,following_count,published_tests_count,total_test_likes_received,total_test_reviews_received,total_test_bookmarks_received,average_test_rating',
                'userOnboardingProfile:user_id,education_level',
                'userInterestSelections:id,user_id,interest_id,slot_no',
                'userInterestSelections.interest:id,name',
            ])
            ->findOrFail($userId);
    }

    public function getApprovedAcademicVerificationForUser(int $userId): ?UserAcademicVerificationRequest
    {
        return UserAcademicVerificationRequest::query()
            ->select([
                'id',
                'user_id',
                'reviewer_user_id',
                'reviewed_at',
                'status',
            ])
            ->with([
                'reviewerUser:id,name,role_id',
                'reviewerUser.userProfile:user_id,avatar_path,avatar_disk',
                'reviewerUser.role:id,name',
            ])
            ->where('user_id', $userId)
            ->where('status', Status::APPROVED->value)
            ->latest('reviewed_at')
            ->first();
    }

    public function getUserTestsRatingDistribution(int $userId): array
    {
        $rows = TestReview::query()
            ->join('test', 'test.id', '=', 'test_reviews.test_id')
            ->where('test.creator_user_id', $userId)
            ->selectRaw('test_reviews.rating, COUNT(*) as count')
            ->groupBy('test_reviews.rating')
            ->pluck('count', 'rating');

        $total = (int) $rows->sum();

        $distribution = [];

        for ($star = 1; $star <= 5; $star++) {
            $count = (int) ($rows[$star] ?? 0);

            $distribution[(string) $star] = [
                'count' => $count,
                'percentage' => $total > 0
                    ? round(($count / $total) * 100, 1)
                    : 0,
            ];
        }

        return [
            'total_ratings' => $total,
            'distribution' => $distribution,
        ];
    }

    public function getUserPublicTests(int $userId): Collection
    {
        return Test::query()
            ->withTrashed()
            ->select([
                'id',
                'creator_user_id',
                'title',
                'description',
                'difficulty_level',
                'price',
                'average_rating',
                'question_count',
                'published_at',
                'created_at'
            ])
            ->with([
                'testIntersetSelections:id,test_id,interest_id,slot_no',
                'testIntersetSelections.interest:id,name',
            ])
            ->where('creator_user_id', $userId)
            ->where('test_type', TestType::Public->value)
            ->latest('published_at')
            ->latest('id')
            ->get();
    }

    public function getUserPublicTestsStatsForYear(int $userId, int $year): array
    {
        $stats = Test::query()
            ->withTrashed()
            ->where('creator_user_id', $userId)
            ->where('test_type', TestType::Public->value)
            ->whereYear('created_at', $year)
            ->selectRaw('
            COUNT(*) as total_tests_count,
            SUM(CASE WHEN price IS NULL OR price = 0 THEN 1 ELSE 0 END) as free_tests_count,
            SUM(CASE WHEN price IS NOT NULL AND price > 0 THEN 1 ELSE 0 END) as paid_tests_count
        ')
            ->first();

        return [
            'total_tests_count' => (int) ($stats->total_tests_count ?? 0),
            'free_tests_count' => (int) ($stats->free_tests_count ?? 0),
            'paid_tests_count' => (int) ($stats->paid_tests_count ?? 0),
        ];
    }

    public function getUserPublicLibraryMaterials(int $userId): Collection
    {
        return LibraryMaterial::query()
            ->select([
                'id',
                'creator_user_id',
                'title',
                'description',
                'content_kind',
                'visibility_type',
                'review_status',
                'published_at',
                'created_at',
                'like_count',
            ])
            ->with([
                'firstAsset:id,library_material_id,storage_path,position,storage_disk',
                'interests:id,name',
            ])
            ->where('creator_user_id', $userId)
            ->where('visibility_type', VisibilityType::Public->value)
            ->latest('published_at')
            ->latest('id')
            ->get();
    }

    public function getUserPublicLibraryMaterialStatsForYear(int $userId, int $year): array
    {
        $stats = LibraryMaterial::query()
            ->where('creator_user_id', $userId)
            ->where('visibility_type', VisibilityType::Public->value)
            ->whereYear('created_at', $year)
            ->selectRaw("
            COUNT(*) as total_materials_count,
            SUM(CASE WHEN content_kind = 'ملف' THEN 1 ELSE 0 END) as files_count,
            SUM(CASE WHEN content_kind <> 'ملف' THEN 1 ELSE 0 END) as image_groups_count
        ")
            ->first();

        return [
            'total_materials_count' => (int) ($stats->total_materials_count ?? 0),
            'files_count' => (int) ($stats->files_count ?? 0),
            'image_groups_count' => (int) ($stats->image_groups_count ?? 0),
        ];
    }

    public function getUserPublicFolders(int $userId): Collection
    {
        return TestFolder::query()
            ->select([
                'id',
                'creator_user_id',
                'name',
                'color_code',
                'visibility_type',
                'contained_test_type',
                'tests_count',
                'published_at',
                'created_at',
            ])
            ->where('creator_user_id', $userId)
            ->where('visibility_type', VisibilityType::Public->value)
            ->where('contained_test_type', TestType::Public->value)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();
    }

    public function getUserPublicFoldersStatsForYear(int $userId, int $year): array
    {
        $total = TestFolder::query()
            ->where('creator_user_id', $userId)
            ->where('visibility_type',  VisibilityType::Public->value)
            ->where('contained_test_type', TestType::Public->value)
            ->whereYear('created_at', $year)
            ->count();

        return [
            'total_folders_count' => $total,
        ];
    }

    public function attachScientificInterestsToFolders(Collection $folders): void
    {
        $folderIds = $folders->pluck('id');

        if ($folderIds->isEmpty()) {
            return;
        }

        $interestsByFolder = DB::table('test_folder_item')
            ->join('test', 'test_folder_item.test_id', '=', 'test.id')
            ->join('test_interset_selections', 'test.id', '=', 'test_interset_selections.test_id')
            ->join('interests', 'test_interset_selections.interest_id', '=', 'interests.id')
            ->whereIn('test_folder_item.test_folder_id', $folderIds)
            ->where('test.test_type', TestType::Public->value)
            ->where('test.review_status', TestReviewStatus::Approved->value)
            ->select([
                'test_folder_item.test_folder_id',
                'interests.name',
            ])
            ->distinct()
            ->orderBy('interests.name')
            ->get()
            ->groupBy('test_folder_id');

        $folders->each(function ($folder) use ($interestsByFolder) {
            $folder->scientific_interests = $interestsByFolder
                ->get($folder->id, collect())
                ->pluck('name')
                ->values()
                ->toArray();
        });
    }

    public function hasActiveBanForUserWithLock(int $userId): bool
    {
        return UserBan::query()
            ->where('user_id', $userId)
            ->whereNull('lifted_at')
            ->where(function ($query) {
                $query->where('ban_type', BanType::Permanent->value)
                    ->orWhere(function ($query) {
                        $query->where('ban_type', BanType::Temporary->value)
                            ->where('ends_at', '>', now());
                    });
            })
            ->lockForUpdate()
            ->exists();
    }

    public function createUserBan(array $data): UserBan
    {
        return UserBan::query()->create($data);
    }

    public function getUserBanHistory(int $userId): \Illuminate\Support\Collection|EnumeratesValues
    {
        return UserBan::query()
            ->select([
                'id',
                'user_id',
                'imposed_by_user_id',
                'ban_type',
                'reason',
                'starts_at',
                'ends_at',
                'lifted_at',
                'created_at',
            ])
            ->with([
                'imposedByUser'=> function ($query) {
                    $query->withTrashed()
                        ->select(['id', 'role_id', 'name']);
                },
                'imposedByUser.userProfile:user_id,avatar_path,avatar_disk',
                'imposedByUser.role:id,name',
            ])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->values()
            ->each(function ($ban, int $index) {
                $ban->serial_no = $index + 1;
            });
    }

    public function getSupervisorProfile(int $userId): ?User
    {
        return User::query()
            ->select([
                'users.id',
                'users.role_id',
                'users.name',
                'users.email',
                'users.gender',
            ])
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->with([
                'userProfile:user_id,phone,governorate',
                'role:id,name',
            ])
            ->where('users.id', $userId)
            ->whereIn('roles.name', [SystemRole::Supervisor->value , SystemRole::Owner->value])
            ->first();
    }

    public function getSupervisorForDeleteWithLock(int $supervisorId): ?User
    {
        return User::query()
            ->select([
                'users.id',
                'users.role_id',
                'users.name',
                'users.email',
            ])
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('users.id', $supervisorId)
            ->where('roles.name', SystemRole::Supervisor->value)
            ->lockForUpdate()
            ->first();
    }

    public function softDeleteUser(User $user): bool
    {
        return (bool) $user->delete();
    }

    public function updateUser(int $adminId, array $data): bool
    {
        return User::query()->whereKey($adminId)->update($data) > 0 ;
    }

    public function updateOrCreateUserProfile(int $adminId, array $data): UserProfile
    {
        return UserProfile::query()->updateOrCreate(
            ['user_id' => $adminId],
            $data
        );
    }

    public function updateUserPassword(User $user, string $hashedPassword): bool
    {
        return $user->forceFill([
            'password' => $hashedPassword,
        ])->save();
    }

    public function getLatestLiftableBanForUserWithLock(int $userId): ?UserBan
    {
        return UserBan::query()
            ->where('user_id', $userId)
            ->whereNull('lifted_at')
            ->where(function ($query) {
                $query->where('ban_type', BanType::Permanent->value)
                    ->orWhere(function ($query) {
                        $query->where('ban_type', BanType::Temporary->value)
                            ->where('ends_at', '>', now());
                    });
            })
            ->latest('id')
            ->lockForUpdate()
            ->first();
    }

    public function liftBan(UserBan $ban, int $liftedByUserId): bool
    {
        return $ban->update([
            'lifted_by_user_id' => $liftedByUserId,
            'lifted_at' => now(),
        ]);
    }
}
