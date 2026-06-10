<?php

namespace App\Repositories\Profile;

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

    public function upsertUniversityProfile(
        int $userId,
        string $universityName,
        string $department,
        ?string $universityYear = null
    ): void {
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

    public function hasApprovedAcademicVerificationRequest(int $userId): bool
    {
        return DB::table('user_academic_verification_requests')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->exists();
    }

    public function hasPendingAcademicVerificationRequest(int $userId): bool
    {
        return DB::table('user_academic_verification_requests')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->exists();
    }

    public function createAcademicVerificationRequest(int $userId): int
    {
        return DB::table('user_academic_verification_requests')->insertGetId([
            'user_id' => $userId,
            'status' => 'pending',
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function createAcademicVerificationAsset(
        int $verificationRequestId,
        string $assetType,
        string $storagePath,
        string $originalName,
        string $mimeType
    ): void {
        DB::table('user_academic_verification_assets')->insert([
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
}
