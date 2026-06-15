<?php

namespace App\Repositories\Auth;

use App\Enums\Status;
use App\Models\InterestCategory;
use App\Models\User;
use App\Models\UserAcademicAsset;
use App\Models\UserAcademicVerificationRequest;
use App\Models\UserInterestSelection;
use App\Models\UserOnboardingProfile;
use App\Models\UserProfile;
use App\Models\UserSchoolProfile;
use App\Models\UserUniversityProfile;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OnboardingRepository
{
    //------------------------------[discovery-source]------------------------------//
    public function findUserByEmail(string $email): ?User
    {
        return User::query()
            ->select(['id', 'email', 'email_verified_at' , 'onboarding_completed_at' , 'gender'])
            ->where('email', $email)
            ->first();
    }
    public function findOnboardingProfileByUserId(int $userId): UserOnboardingProfile|Builder|null
    {
        return UserOnboardingProfile::query()
            ->where('user_id', $userId)
            ->first();
    }
    public function createOnboardingProfile(array $data): UserOnboardingProfile
    {
        return UserOnboardingProfile::query()->create($data);
    }
    public function updateOnboardingProfile(UserOnboardingProfile $profile, array $data): UserOnboardingProfile {
        $profile->update($data);
        return $profile->refresh();
    }

    //------------------------------[education-level]------------------------------//

    public function createUserProfile(array $data): UserProfile
    {
        return UserProfile::query()->create($data);
    }

    public function updateUserProfile(UserProfile $profile, array $data): UserProfile
    {
        $profile->update($data);

        return $profile->refresh();
    }

    public function findUserProfileByUserId(int $userId): UserProfile|Builder|null
    {
        return UserProfile::query()
            ->where('user_id', $userId)
            ->first();
    }

    //------------------------------[school-stage]------------------------------//
    public function findSchoolProfileByUserId(int $userId): UserSchoolProfile|Builder|null
    {
        return UserSchoolProfile::query()
            ->where('user_id', $userId)
            ->first();
    }

    public function createSchoolProfile(array $data): UserSchoolProfile
    {
        return UserSchoolProfile::query()->create($data);
    }

    public function updateSchoolProfile(UserSchoolProfile $profile, array $data): UserSchoolProfile {
        $profile->update($data);
        return $profile->refresh();
    }

    //------------------------------[university-profile]------------------------------//

    public function findUniversityProfileByUserId(int $userId): UserUniversityProfile|Builder|null
    {
        return UserUniversityProfile::query()
            ->where('user_id', $userId)
            ->first();
    }

    public function createUniversityProfile(array $data): UserUniversityProfile
    {
        return UserUniversityProfile::query()->create($data);
    }

    public function updateUniversityProfile(UserUniversityProfile $profile, array $data): UserUniversityProfile {
        $profile->update($data);
        return $profile->refresh();
    }


    //------------------------------[graduate-academic-profile]------------------------------//

    public function findPendingAcademicVerificationRequestByUserId(int $userId): UserAcademicVerificationRequest|Builder|null
    {
        return UserAcademicVerificationRequest::query()
            ->where('user_id', $userId)
            ->where('status', Status::PENDING->value)
            ->first();
    }

    public function createAcademicVerificationRequest(array $data): UserAcademicVerificationRequest
    {
        return UserAcademicVerificationRequest::query()->create($data);
    }

    public function createAcademicVerificationAsset(array $data): UserAcademicAsset
    {
        return UserAcademicAsset::query()->create($data);
    }

    //------------------------------[user-interest]------------------------------//

    public function createUserInterestSelections(array $rows): void
    {
        UserInterestSelection::query()->insert($rows);
    }

    public function updateUserOnboardingCompletedAt(int $userId , CarbonInterface $completedAt): void
    {
        User::query()
            ->whereKey($userId)
            ->update([
                'onboarding_completed_at' => $completedAt,
                'updated_at' => now(),
            ]);
    }

    public function deleteUserInterestSelections(int $userId): void
    {
        UserInterestSelection::query()
            ->where('user_id', $userId)
            ->delete();
    }

    //------------------------------[interest]------------------------------//

    public function getInterestCategoriesWithInterests(): Collection
    {
        return InterestCategory::query()
            ->select(['id', 'title'])
            ->with([
                'interests:id,interest_category_id,name',
            ])
            ->get();
    }

}
