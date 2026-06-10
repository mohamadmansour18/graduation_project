<?php

namespace App\Repositories\Profile;

use App\Enums\AcademicAssetType;
use App\Enums\LibraryMaterialContentKind;
use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\Status;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Enums\VisibilityType;
use App\Models\LibraryMaterial;
use App\Models\Test;
use App\Models\TestFolder;
use App\Models\TestReview;
use App\Models\User;
use App\Models\UserAcademicAsset;
use App\Models\UserProfile;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LaravelIdea\Helper\App\Models\_IH_Test_C;

class PublicProfileRepository
{
    public function findOverviewUser(int $profileUserId, int $viewerUserId): User
    {
        return User::query()
            ->select([
                'id',
                'name',
                'gender',
                'created_at',
                'is_academically_verified',
            ])
            ->with([
                'userProfile:id,user_id,avatar_disk,avatar_path,cover_disk,cover_path,profile_slug,governorate',
                'userProfileStat:id,user_id,followers_count,following_count,published_tests_count,average_test_rating,total_test_likes_received,total_test_reviews_received,total_test_bookmarks_received',
                'userOnboardingProfile:id,user_id,education_level',
                'userInterestSelections:id,user_id,interest_id,slot_no',
                'userInterestSelections.interest:id,name,interest_category_id',
            ])
            ->withExists([
                'followedUserFollows as viewer_is_following' => fn ($query) =>
                $query->where('follower_user_id', $viewerUserId),
            ])
            ->findOrFail($profileUserId);
    }

    public function getRatingCountsForUserTests(int $profileUserId): \Illuminate\Support\Collection
    {
        return TestReview::query()
            ->join('test', 'test_reviews.test_id', '=', 'test.id')
            ->where('test.creator_user_id', $profileUserId)
            ->where('test.test_type', TestType::Public->value)
            ->where('test.review_status', TestReviewStatus::Approved->value)
            ->selectRaw('test_reviews.rating, COUNT(*) as rating_count')
            ->groupBy('test_reviews.rating')
            ->pluck('rating_count', 'test_reviews.rating');
    }

    public function cursorPaginatePublishedTestsForUser(int $profileUserId, string $tab, int $perPage): CursorPaginator
    {
        $query = Test::query()
            ->select([
                'id',
                'creator_user_id',
                'title',
                'description',
                'target_level',
                'average_rating',
                'price',
                'published_at',
                'question_count',
                'participants_count',
            ])
            ->where('creator_user_id', $profileUserId)
            ->where('test_type', TestType::Public->value)
            ->where('review_status', TestReviewStatus::Approved->value)
            ->with([
                'testIntersetSelections:id,test_id,interest_id,slot_no',
                'testIntersetSelections.interest:id,name,interest_category_id',
            ]);

        match ($tab) {
            'paid' => $query
                ->whereNotNull('price')
                ->where('price', '>', 0)
                ->orderByDesc('published_at')
                ->orderByDesc('id'),

            'free' => $query
                ->where(function ($query) {
                    $query->whereNull('price')
                        ->orWhere('price', 0);
                })
                ->orderByDesc('published_at')
                ->orderByDesc('id'),

            'popular' => $query
                ->orderByDesc('participants_count')
                ->orderByDesc('published_at')
                ->orderByDesc('id'),

            default => $query
                ->orderByDesc('published_at')
                ->orderByDesc('id'),
        };

        return $query->cursorPaginate($perPage);
    }

    public function cursorPaginatePublicFoldersForUser(int $profileUserId, int $viewerUserId, int $perPage): CursorPaginator
    {
        $paginator = TestFolder::query()
            ->select([
                'id',
                'creator_user_id',
                'name',
                'color_code',
                'tests_count',
                'published_at',
            ])
            ->where('creator_user_id', $profileUserId)
            ->where('visibility_type', VisibilityType::Public->value)
            ->where('contained_test_type', TestType::Public->value)
            ->withExists([
                'testFolderBookmarks as viewer_has_bookmarked' => fn ($query) =>
                $query->where('user_id', $viewerUserId),
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);

        $this->attachScientificInterests($paginator);

        return $paginator;
    }

    private function attachScientificInterests(CursorPaginator $paginator): void
    {
        $folderIds = collect($paginator->items())->pluck('id');

        if ($folderIds->isEmpty()) {
            return;
        }

        $interestsByFolder = DB::table('test_folder_item')
            ->join('test', 'test_folder_item.test_id', '=', 'test.id')
            ->join('test_interset_selections', 'test.id', '=', 'test_interset_selections.test_id')
            ->join('interests', 'test_interset_selections.interest_id', '=', 'interests.id')
            ->whereIn('test_folder_item.test_folder_id', $folderIds)
            ->where('test.test_type', 'public')
            ->where('test.review_status', 'approved')
            ->select([
                'test_folder_item.test_folder_id',
                'interests.id',
                'interests.name',
            ])
            ->distinct()
            ->orderBy('interests.name')
            ->get()
            ->groupBy('test_folder_id');

        collect($paginator->items())->each(function ($folder) use ($interestsByFolder) {
            $folder->scientific_interests = $interestsByFolder
                ->get($folder->id, collect())
                ->map(fn ($interest) => [
                    'id' => $interest->id,
                    'name' => $interest->name,
                ])
                ->values();
        });
    }

    public function cursorPaginatePublicMaterialsForUser(int $profileUserId, int $viewerUserId, string $tab, int $perPage): CursorPaginator
    {
        $query = LibraryMaterial::query()
            ->select([
                'id',
                'creator_user_id',
                'title',
                'description',
                'content_kind',
                'visibility_type',
                'review_status',
                'published_at',
                'like_count',
            ])
            ->where('creator_user_id', $profileUserId)
            ->where('visibility_type', VisibilityType::Public->value)
            ->where('review_status', LibraryMaterialReviewStatus::Approved->value)
            ->with([
                'firstAsset:id,library_material_id,storage_path,position',
                'interests:id,name',
            ])
            ->withExists([
                'libraryMaterialBookmarks as viewer_has_bookmarked' => fn ($query) =>
                $query->where('user_id', $viewerUserId),
            ]);

        match ($tab) {
            'popular' => $query
                ->orderByDesc('like_count')
                ->orderByDesc('published_at')
                ->orderByDesc('id'),

            'files' => $query
                ->where('content_kind', LibraryMaterialContentKind::File->value)
                ->orderByDesc('published_at')
                ->orderByDesc('id'),

            'images' => $query
                ->where('content_kind', LibraryMaterialContentKind::ImageGroup->value)
                ->orderByDesc('published_at')
                ->orderByDesc('id'),

            default => $query
                ->orderByDesc('published_at')
                ->orderByDesc('id'),
        };

        return $query->cursorPaginate($perPage);
    }

    public function getOrCreateProfileSlug(int $userId): string
    {
        $profile = UserProfile::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();

        if (! $profile) {
            $profile = UserProfile::query()->create([
                'user_id' => $userId,
                'profile_slug' => $this->generateSlug($userId),
            ]);

            return $profile->profile_slug;
        }

        if ($profile->profile_slug) {
            return $profile->profile_slug;
        }

        $profile->forceFill([
            'profile_slug' => $this->generateSlug($userId),
        ])->save();

        return $profile->profile_slug;
    }

    public function findUserByProfileSlug(string $slug): ?User
    {
        return User::query()
            ->select(['users.id'])
            ->join('user_profile', 'users.id', '=', 'user_profile.user_id')
            ->where('user_profile.profile_slug', $slug)
            ->first();
    }

    private function generateSlug(int $userId): string
    {
        do {
            $slug = 'u-' . $userId . '-' . Str::lower(Str::random(8));
        } while (
            UserProfile::query()
                ->where('profile_slug', $slug)
                ->exists()
        );

        return $slug;
    }

    public function findPublicFolderForViewer(int $folderId, int $viewerUserId): ?TestFolder
    {
        return TestFolder::query()
            ->select([
                'id',
                'creator_user_id',
                'name',
                'tests_count',
                'published_at',
            ])
            ->where('id', $folderId)
            ->where('visibility_type', VisibilityType::Public->value)
            ->where('contained_test_type', TestType::Public->value)
            ->withExists([
                'testFolderBookmarks as viewer_has_bookmarked' => fn ($query) =>
                $query->where('user_id', $viewerUserId),
            ])
            ->first();
    }

    public function FolderTests(int $folderId): array|Collection|_IH_Test_C
    {
        return Test::query()
            ->select([
                'test.id',
                'test.creator_user_id',
                'test.title',
                'test.description',
                'test.target_level',
                'test.average_rating',
                'test.price',
                'test.published_at',
                'test.question_count',
                'test_folder_item.position',
            ])
            ->join('test_folder_item', 'test.id', '=', 'test_folder_item.test_id')
            ->where('test_folder_item.test_folder_id', $folderId)
            ->where('test.test_type', TestType::Public->value)
            ->where('test.review_status', TestReviewStatus::Approved->value)
            ->with([
                'testIntersetSelections:id,test_id,interest_id,slot_no',
                'testIntersetSelections.interest:id,name',
            ])
            ->orderBy('test_folder_item.position')
            ->orderBy('test.id')
            ->get();
    }

    public function findApprovedCertificateForUser(int $userId): ?UserAcademicAsset
    {
        return UserAcademicAsset::query()
            ->select([
                'user_academic_assets.id',
                'user_academic_assets.verification_request_id',
                'user_academic_assets.asset_type',
                'user_academic_assets.storage_disk',
                'user_academic_assets.storage_path',
                'user_academic_assets.original_name',
                'user_academic_assets.mime_type',
            ])
            ->join(
                'user_academic_verification_requests',
                'user_academic_assets.verification_request_id',
                '=',
                'user_academic_verification_requests.id'
            )
            ->where('user_academic_verification_requests.user_id', $userId)
            ->where('user_academic_verification_requests.status', Status::APPROVED->value)
            ->where('user_academic_assets.asset_type', AcademicAssetType::University_Certificate->value)
            ->latest('user_academic_verification_requests.reviewed_at')
            ->first();
    }
    //TODO: يجب ان نجد حلا لتخزين الهوية الشخصية كما هي بدون اي تشفير او تغير في المحتوى

    public function cursorPaginateFollowers(int $profileUserId, int $viewerUserId, ?string $search, int $perPage): CursorPaginator
    {
        return $this->baseFollowUserQuery($viewerUserId, $search)
            ->join('user_follows', 'users.id', '=', 'user_follows.follower_user_id')
            ->where('user_follows.followed_user_id', $profileUserId)
            ->where('users.id', '!=', $viewerUserId)
            ->orderByDesc('user_follows.id')
            ->orderByDesc('users.id')
            ->cursorPaginate($perPage);
    }

    public function cursorPaginateFollowing(int $profileUserId, int $viewerUserId, ?string $search, int $perPage): CursorPaginator
    {
        return $this->baseFollowUserQuery($viewerUserId, $search)
            ->join('user_follows', 'users.id', '=', 'user_follows.followed_user_id')
            ->where('user_follows.follower_user_id', $profileUserId)
            ->where('users.id', '!=', $viewerUserId)
            ->orderByDesc('user_follows.id')
            ->orderByDesc('users.id')
            ->cursorPaginate($perPage);
    }

    private function baseFollowUserQuery(int $viewerUserId, ?string $search): \LaravelIdea\Helper\App\Models\_IH_User_QB|\Illuminate\Database\Eloquent\Builder|\Illuminate\Support\HigherOrderWhenProxy
    {
        return User::query()
            ->select([
                'users.id',
                'users.name',
                'users.is_academically_verified',
            ])
            ->with([
                'userProfile:id,user_id,avatar_disk,avatar_path',
                'userOnboardingProfile:id,user_id,education_level',
            ])
            ->withExists([
                'followedUserFollows as viewer_is_following' => fn ($query) =>
                $query->where('follower_user_id', $viewerUserId),
            ])
            ->when($search, function ($query) use ($search) {
                $query->where('users.name', 'like',  addcslashes($search, '%_\\') . '%');
            });
    }
}
