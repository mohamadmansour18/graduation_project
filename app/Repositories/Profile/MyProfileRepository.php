<?php

namespace App\Repositories\Profile;

use App\Enums\LibraryMaterialReviewStatus;
use App\Enums\Status;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Enums\VisibilityType;
use App\Models\LibraryMaterial;
use App\Models\Test;
use App\Models\TestFolder;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class MyProfileRepository
{
    public function getBasicInfoByUserId(int $userId): ?array
    {
        $user = DB::table('users')
            ->leftJoin('user_profile', 'users.id', '=', 'user_profile.user_id')
            ->leftJoin('user_profile_stats', 'users.id', '=', 'user_profile_stats.user_id')
            ->leftJoin('user_onboarding_profiles', 'users.id', '=', 'user_onboarding_profiles.user_id')
            ->leftJoin('user_university_profiles', 'users.id', '=', 'user_university_profiles.user_id')
            ->leftJoin('user_school_profiles', 'users.id', '=', 'user_school_profiles.user_id')
            ->where('users.id', $userId)
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.gender',
                'users.is_academically_verified',
                'users.created_at',

                'user_profile.phone',
                'user_profile.birth_date',
                'user_profile.avatar_disk',
                'user_profile.avatar_path',
                'user_profile.cover_disk',
                'user_profile.cover_path',

                'user_profile.governorate',

                'user_profile_stats.followers_count',
                'user_profile_stats.following_count',
                'user_profile_stats.published_tests_count',

                'user_onboarding_profiles.education_level',

                'user_university_profiles.university_name',
                'user_university_profiles.department',
                'user_university_profiles.university_year',

                'user_school_profiles.school_stage',
            ])
            ->first();

        if (! $user) {
            return null;
        }

        $interests = DB::table('user_interest_selections')
            ->join('interests', 'user_interest_selections.interest_id', '=', 'interests.id')
            ->where('user_interest_selections.user_id', $userId)
            ->orderBy('user_interest_selections.slot_no')
            ->select([
                'interests.id',
                'interests.name',
            ])
            ->get()
            ->map(fn ($interest) => [
                'id' => (int) $interest->id,
                'name' => $interest->name,
            ])
            ->values()
            ->all();

        return [
            'user' => (array) $user,
            'interests' => $interests,
        ];
    }

    public function ensureUserProfileRow(int $userId): void
    {
        DB::table('user_profile')->updateOrInsert(
            ['user_id' => $userId],
            [
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function updateUserPersonalData(int $userId, array $data): void
    {
        if ($data === []) {
            return;
        }

        $data['updated_at'] = now();

        DB::table('users')
            ->where('id', $userId)
            ->update($data);
    }

    public function updateUserProfileData(int $userId, array $data): void
    {
        if ($data === []) {
            return;
        }

        $data['updated_at'] = now();

        DB::table('user_profile')
            ->where('user_id', $userId)
            ->update($data);
    }

    public function getAcademicSnapshotForUpdate(int $userId): ?array
    {
        $row = DB::table('users')
            ->join('user_onboarding_profiles', 'users.id', '=', 'user_onboarding_profiles.user_id')
            ->leftJoin('user_school_profiles', 'users.id', '=', 'user_school_profiles.user_id')
            ->leftJoin('user_university_profiles', 'users.id', '=', 'user_university_profiles.user_id')
            ->where('users.id', $userId)
            ->lockForUpdate()
            ->select([
                'users.id',
                'users.is_academically_verified',

                'user_onboarding_profiles.education_level',

                'user_school_profiles.school_stage',

                'user_university_profiles.university_name',
                'user_university_profiles.department',
                'user_university_profiles.university_year',
            ])
            ->first();

        return $row ? (array) $row : null;
    }

    public function updateEducationLevel(int $userId, string $educationLevel): void
    {
        DB::table('user_onboarding_profiles')
            ->where('user_id', $userId)
            ->update([
                'education_level' => $educationLevel,
                'updated_at' => now(),
            ]);
    }

    public function upsertSchoolProfile(int $userId, string $schoolStage): void
    {
        DB::table('user_school_profiles')->updateOrInsert(
            ['user_id' => $userId],
            [
                'school_stage' => $schoolStage,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function deleteUniversityProfile(int $userId): void
    {
        DB::table('user_university_profiles')
            ->where('user_id', $userId)
            ->delete();
    }

    public function upsertUniversityProfile(int $userId, string $universityName, string $department, ?string $universityYear = null): void
    {
        DB::table('user_university_profiles')->updateOrInsert(
            ['user_id' => $userId],
            [
                'university_name' => $universityName,
                'department' => $department,
                'university_year' => $universityYear,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function deleteSchoolProfile(int $userId): void
    {
        DB::table('user_school_profiles')
            ->where('user_id', $userId)
            ->delete();
    }

    public function isUserAcademicallyVerified(int $userId): bool
    {
        return DB::table('users')
            ->where('id', $userId)
            ->where('is_academically_verified', true)
            ->exists();
    }

    public function hasPendingAcademicVerificationRequest(int $userId): bool
    {
        return DB::table('user_academic_verification_requests')
            ->where('user_id', $userId)
            ->where('status', Status::PENDING->value)
            ->exists();
    }

    public function createAcademicVerificationRequest(int $userId): int
    {
        return DB::table('user_academic_verification_requests')->insertGetId([
            'user_id' => $userId,
            'status' => Status::PENDING->value,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createAcademicVerificationAsset(int $verificationRequestId, string $assetType, string $storagePath, string $originalName, string $mimeType): void
    {
        DB::table('user_academic_assets')->insert([
            'verification_request_id' => $verificationRequestId,
            'asset_type' => $assetType,
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function replaceScientificInterests(int $userId, array $interestIds): void
    {
        DB::table('user_interest_selections')
            ->where('user_id', $userId)
            ->delete();

        $rows = [];

        foreach (array_values($interestIds) as $index => $interestId) {
            $rows[] = [
                'user_id' => $userId,
                'interest_id' => $interestId,
                'slot_no' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('user_interest_selections')->insert($rows);
    }

    public function getProfilePhotoDataForUpdate(int $userId): ?array
    {
        $row = DB::table('user_profile')
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->select([
                'avatar_disk',
                'avatar_path',
                'cover_disk',
                'cover_path',
            ])
            ->first();

        return $row ? (array) $row : null;
    }

    public function updateAvatar(int $userId, string $disk, string $path): void
    {
        DB::table('user_profile')
            ->where('user_id', $userId)
            ->update([
                'avatar_disk' => $disk,
                'avatar_path' => $path,
                'updated_at' => now(),
            ]);
    }

    public function updateCover(int $userId, string $disk, string $path): void
    {
        DB::table('user_profile')
            ->where('user_id', $userId)
            ->update([
                'cover_disk' => $disk,
                'cover_path' => $path,
                'updated_at' => now(),
            ]);
    }

    public function clearAvatar(int $userId): void
    {
        DB::table('user_profile')
            ->where('user_id', $userId)
            ->update([
                'avatar_disk' => null,
                'avatar_path' => null,
                'updated_at' => now(),
            ]);
    }

    public function clearCover(int $userId): void
    {
        DB::table('user_profile')
            ->where('user_id', $userId)
            ->update([
                'cover_disk' => null,
                'cover_path' => null,
                'updated_at' => now(),
            ]);
    }

    public function getMyCreatedTestsByTab(int $userId, string $tab, int $perPage): CursorPaginator
    {
        return Test::query()
            ->where('creator_user_id', $userId)
            ->when($tab === 'public', function ($query) {
                $query->where('test_type', TestType::Public->value);
            })
            ->when($tab === 'private', function ($query) {
                $query->where('test_type', TestType::Private->value);
            })
            ->when($tab === 'paid', function ($query) {
                $query->where('test_type', TestType::Public->value)
                    ->whereNotNull('price')
                    ->where('price', '>', 0);
            })
            ->with([
                'testIntersetSelections:id,test_id,interest_id',
                'testIntersetSelections.interest:id,name',
            ])
            ->select([
                'id',
                'creator_user_id',
                'title',
                'description',
                'test_type',
                'target_level',
                'average_rating',
                'price',
                'published_at',
                'question_count',
                'created_at',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }

    public function getMyLibraryMaterialsByTab(int $userId, string $tab, int $perPage): CursorPaginator
    {
        return LibraryMaterial::query()
            ->where('creator_user_id', $userId)
            ->when($tab === 'private', function ($query) {
                $query->where('visibility_type', VisibilityType::Private->value);
            })
            ->when($tab === 'public', function ($query) {
                $query->where('visibility_type', VisibilityType::Public->value);
            })
            ->with([
                'firstAsset:id,library_material_id,storage_path,position',
                'interests:id,name',
            ])
            ->withExists([
                'libraryMaterialBookmarks as viewer_has_bookmarked' => function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])
            ->select([
                'id',
                'creator_user_id',
                'title',
                'description',
                'content_kind',
                'visibility_type',
                'like_count',
                'published_at',
                'created_at',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
    }

    public function cursorPaginateUserFolders(int $userId , string $tab , int $perPage): CursorPaginator
    {
        $paginator = TestFolder::query()
            ->where('creator_user_id', $userId)
            ->when($tab === 'private', function ($query) {
                $query->where('visibility_type', VisibilityType::Private->value);
            })
            ->when($tab === 'public', function ($query) {
                $query->where('visibility_type', VisibilityType::Public->value);
            })
            ->select([
                'id',
                'creator_user_id',
                'name',
                'color_code',
                'tests_count',
                'published_at',
                'visibility_type',
                'created_at',
            ])
            ->orderByDesc('created_at')
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
            ->where('test.test_type', TestType::Public->value)
            ->where('test.review_status', TestReviewStatus::Approved->value)
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
//                ->map(fn ($interest) => [
//                    'id' => $interest->id,
//                    'name' => $interest->name,
//                ])
                ->pluck('name')
                ->values();
        });
    }

    public function cursorPaginateBookmarkedTests(int $userId, int $perPage): CursorPaginator
    {
        return Test::query()
            ->select([
                'test.id',
                'test.title',
                'test.description',
                'test.difficulty_level',
                'test.average_rating',
                'test.price',
                'test.published_at',
                'test.question_count',
                'test_bookmarks.created_at',
            ])
            ->join('test_bookmarks', 'test.id', '=', 'test_bookmarks.test_id')
            ->where('test_bookmarks.user_id', $userId)
            ->where('test.test_type', TestType::Public->value)
            ->where('test.review_status', TestReviewStatus::Approved->value)
            ->with([
                'testIntersetSelections:id,test_id,interest_id',
                'testIntersetSelections.interest:id,name',
            ])
            ->orderByDesc('test_bookmarks.created_at')
            ->cursorPaginate($perPage);
    }

    public function cursorPaginateBookmarkedMaterials(int $userId, int $perPage): CursorPaginator
    {
        return LibraryMaterial::query()
            ->select([
                'library_material.id',
                'library_material.creator_user_id',
                'library_material.title',
                'library_material.description',
                'library_material.content_kind',
                'library_material.visibility_type',
                'library_material.review_status',
                'library_material.published_at',
                'library_material.like_count',
                'library_material_bookmarks.created_at',
            ])
            ->join(
                'library_material_bookmarks',
                'library_material.id',
                '=',
                'library_material_bookmarks.library_material_id'
            )
            ->where('library_material_bookmarks.user_id', $userId)
            ->where('library_material.visibility_type', VisibilityType::Public->value)
            ->where('library_material.review_status', LibraryMaterialReviewStatus::Approved->value)
            ->with([
                'firstAsset:id,library_material_id,storage_path,position',
                'interests:id,name',
            ])
            ->withExists([
                'libraryMaterialBookmarks as viewer_has_bookmarked' => fn ($query) =>
                $query->where('user_id', $userId),
            ])
            ->orderByDesc('library_material_bookmarks.created_at')
            ->cursorPaginate($perPage);
    }

    public function cursorPaginateBookmarkedFolders(int $userId, int $perPage): CursorPaginator
    {
        $paginator = TestFolder::query()
            ->select([
                'test_folder.id',
                'test_folder.creator_user_id',
                'test_folder.name',
                'test_folder.color_code',
                'test_folder.tests_count',
                'test_folder.created_at',
                'test_folder_bookmarks.created_at',
            ])
            ->join(
                'test_folder_bookmarks',
                'test_folder.id',
                '=',
                'test_folder_bookmarks.test_folder_id'
            )
            ->where('test_folder_bookmarks.user_id', $userId)
            ->where('test_folder.visibility_type', VisibilityType::Public->value)
            ->where('test_folder.contained_test_type', TestType::Public->value)
            ->withExists([
                'testFolderBookmarks as viewer_has_bookmarked' => fn ($query) =>
                $query->where('user_id', $userId),
            ])
            ->orderByDesc('test_folder_bookmarks.created_at')
            ->cursorPaginate($perPage);

        $this->attachScientificInterests($paginator);

        return $paginator;
    }

}
