<?php

namespace App\Services\Auth;

use App\Enums\AcademicAssetType;
use App\Enums\EducationLevel;
use App\Enums\Status;
use App\Exceptions\Api\OnboardingException;
use App\Repositories\Auth\OnboardingRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OnboardingService
{
    public function __construct(
        protected OnboardingRepository $onboardingRepository,
    ){}

    public function saveDiscoverySource(string $email, string $discoverySource): array
    {

        $user = $this->onboardingRepository->findUserByEmail($email);

        if(is_null($user->email_verified_at)){
            throw OnboardingException::emailNotVerifiedForOnboarding();
        }

        $profile  = $this->onboardingRepository->findOnboardingProfileByUserId($user->id);

        if(!$profile)
        {
            $profile = $this->onboardingRepository->createOnboardingProfile([
                'user_id' => $user->id,
                'discovery_source' => $discoverySource,
                'last_completed_step' => 1,
            ]);
        } else {

            $profile = $this->onboardingRepository->updateOnboardingProfile($profile ,[
                'discovery_source' => $discoverySource,
            ]);
        }

        return [
            'onboarding_profile' => [
                'user_id' => $profile->user_id,
                'email' => $user->email,
                'discovery_source' => $profile->discovery_source,
            ],
        ];
    }

    public function saveEducationLevel(string $email , string $governorate , string $educationalLevel):array
    {
        $user = $this->onboardingRepository->findUserByEmail($email);

        if(is_null($user->email_verified_at)){
            throw OnboardingException::emailNotVerifiedForOnboarding();
        }

        $userProfile = $this->onboardingRepository->findUserProfileByUserId($user->id);
        $onboardingProfile = $this->onboardingRepository->findOnboardingProfileByUserId($user->id);

        $result = DB::transaction(function () use ($user , $governorate , $educationalLevel , $userProfile , $onboardingProfile){
            if(!$userProfile)
            {
                $userProfile = $this->onboardingRepository->createUserProfile([
                    'user_id' => $user->id,
                    'governorate' => $governorate,
                    'profile_slug' => 'u-' . $user->id . '-' . Str::lower(Str::random(8)),
                ]);
            }
            else if($userProfile->governorate->value !== $governorate)
            {
                $userProfile = $this->onboardingRepository->updateUserProfile($userProfile ,[
                    'governorate' => $governorate,
                ]);
            }
            if($onboardingProfile->education_level?->value !== $educationalLevel) {
                $onboardingProfile = $this->onboardingRepository->updateOnboardingProfile($onboardingProfile, [
                    'education_level' => $educationalLevel,
                    'last_completed_step' => max( (int)($onboardingProfile->last_completed_step ?? 0) , 2)
                ]);
            }

            return [
                'user_profile' => $userProfile,
                'onboarding_profile' => $onboardingProfile,
            ];
        });

        return [
            'onboarding_profile' => [
                'user_id' => $user->id,
                'email' => $user->email,
                'governorate' => $result['user_profile']?->governorate,
                'education_level' => $result['onboarding_profile']?->education_level,
            ]
        ];
    }

    public function saveSchoolStage(string $email , string $schoolStage): array
    {
        $user = $this->onboardingRepository->findUserByEmail($email);

        if(is_null($user->email_verified_at)){
            throw OnboardingException::emailNotVerifiedForOnboarding();
        }

        $onboardingProfile = $this->onboardingRepository->findOnboardingProfileByUserId($user->id);

        if($onboardingProfile->education_level !== EducationLevel::School)
        {
            throw OnboardingException::invalidEducationPath();
        }

        $schoolProfile = $this->onboardingRepository->findSchoolProfileByUserId($user->id);

        $result = DB::transaction(function () use ($user , $schoolProfile , $onboardingProfile , $schoolStage){
            if(!$schoolProfile)
            {
                $schoolProfile = $this->onboardingRepository->createSchoolProfile([
                    'user_id' => $user->id,
                    'school_stage' => $schoolStage,
                ]);

                $this->onboardingRepository->updateOnboardingProfile($onboardingProfile, [
                    'last_completed_step' => 5
                ]);
            }

            elseif ($schoolProfile->school_stage->value !== $schoolStage)
            {
                $schoolProfile = $this->onboardingRepository->updateSchoolProfile($schoolProfile, [
                    'school_stage' => $schoolStage,
                ]);
            }

            return [
                'school_profile' => $schoolProfile,
            ];
        });

        return [
            'onboarding_profile' => [
                'user_id' => $user->id,
                'email' => $user->email,
                'school_stage' => $result['school_profile']?->school_stage,
            ]
        ];
    }

    public function saveUniversityProfile(string $email, string $universityName, string $department, string $universityYear ): array
    {
        $user = $this->onboardingRepository->findUserByEmail($email);

        if(is_null($user->email_verified_at)){
            throw OnboardingException::emailNotVerifiedForOnboarding();
        }

        $onboardingProfile = $this->onboardingRepository->findOnboardingProfileByUserId($user->id);

        if($onboardingProfile->education_level !== EducationLevel::University)
        {
            throw OnboardingException::invalidEducationPath("جامعة");
        }

        $universityProfile = $this->onboardingRepository->findUniversityProfileByUserId($user->id);

        $result = DB::transaction(function () use ($user , $onboardingProfile , $universityProfile , $universityName , $department , $universityYear ){

            if(!$universityProfile)
            {
                $universityProfile = $this->onboardingRepository->createUniversityProfile([
                    'user_id' => $user->id,
                    'university_name' => $universityName,
                    'department' => $department,
                    'university_year' => $universityYear,
                ]);

                $this->onboardingRepository->updateOnboardingProfile($onboardingProfile, [
                    'last_completed_step' => 4
                ]);
            }
            elseif( $universityProfile->university_name->value !== $universityName ||
                    $universityProfile->department->value !== $department ||
                    $universityProfile->university_year !== $universityYear )
            {
                $universityProfile = $this->onboardingRepository->updateUniversityProfile($universityProfile, [
                    'university_name' => $universityName,
                    'department' => $department,
                    'university_year' => $universityYear,
                ]);
            }

            return [
                'university_profile' => $universityProfile,
            ];
        });

        return [
            'onboarding_profile' => [
                'user_id' => $user->id,
                'email' => $user->email,
                'university_name' => $result['university_profile']?->university_name,
                'department' => $result['university_profile']?->department,
                'university_year' => $result['university_profile']?->university_year,
            ]
        ];
    }

    public function saveGraduateAcademicProfile(string $email, string $universityName, string $department, ?UploadedFile $certificateImage = null, ?UploadedFile $identityImage = null): array
    {
        $user = $this->onboardingRepository->findUserByEmail($email);

        if(is_null($user->email_verified_at)){
            throw OnboardingException::emailNotVerifiedForOnboarding();
        }

        $onboardingProfile = $this->onboardingRepository->findOnboardingProfileByUserId($user->id);

        if(!in_array($onboardingProfile->education_level , [EducationLevel::Graduate , EducationLevel::Master , EducationLevel::PhD] , true))
        {
            throw OnboardingException::invalidEducationPathForGraduateAcademicProfile();
        }

        $pendingRequest = $this->onboardingRepository->findPendingAcademicVerificationRequestByUserId($user->id);
        if($pendingRequest)
        {
            throw OnboardingException::pendingAcademicVerificationRequestExists();
        }

        $certificatePath = null;
        $identityPath = null;

        try {
            if ($certificateImage) {
                $certificatePath = $certificateImage->store('academic-verification/certificates', 'public');
            }

            if ($identityImage) {
                $identityPath = $identityImage->store('academic-verification/identities', 'public');
            }

            $result = DB::transaction(function () use ($user , $onboardingProfile , $universityName , $department , $certificateImage , $certificatePath , $identityImage , $identityPath){

                $universityProfile = $this->onboardingRepository->createUniversityProfile([
                    'user_id' => $user->id,
                    'university_name' => $universityName,
                    'department' => $department,
                ]);

                if ($certificateImage && $identityImage)
                {
                    $verificationRequest = $this->onboardingRepository->createAcademicVerificationRequest([
                        'user_id' => $user->id,
                        'status' => Status::PENDING->value,
                        'submitted_at' => now(),
                    ]);

                    $this->onboardingRepository->createAcademicVerificationAsset([
                        'verification_request_id' => $verificationRequest->id,
                        'asset_type' => AcademicAssetType::University_Certificate->value,
                        'storage_path' => $certificatePath,
                        'original_name' => $certificateImage?->getClientOriginalName(),
                        'mime_type' => $certificateImage?->getClientMimeType(),
                    ]);

                    $this->onboardingRepository->createAcademicVerificationAsset([
                        'verification_request_id' => $verificationRequest->id,
                        'asset_type' => AcademicAssetType::Identity_Card->value,
                        'storage_path' => $identityPath,
                        'original_name' => $identityImage?->getClientOriginalName(),
                        'mime_type' => $identityImage?->getClientMimeType(),
                    ]);
                }

                $this->onboardingRepository->updateOnboardingProfile($onboardingProfile, [
                    'last_completed_step' => 3
                ]);

                return [
                    'university_profile' => $universityProfile,
                ];
            });
        }catch (\Throwable $exception) {
            if ($certificatePath) {
                Storage::disk('public')->delete($certificatePath);
            }

            if ($identityPath) {
                Storage::disk('public')->delete($identityPath);
            }

            Log::channel('errors')->error("Error saving graduate academic profile for user_id: {$user->id}, error: {$exception->getMessage()}");
            throw $exception;
        }

        return [
            'onboarding_profile' => [
                'user_id' => $user->id,
                'email' => $user->email,
                'university_name' => $result['university_profile']?->university_name,
                'department' => $result['university_profile']?->department,
            ]
        ];
    }

    public function saveUserInterests(string $email, array $interestIds):array
    {
        $user = $this->onboardingRepository->findUserByEmail($email);

        if(is_null($user->email_verified_at)){
            throw OnboardingException::emailNotVerifiedForOnboarding();
        }

        if (!is_null($user->onboarding_completed_at)) {
            throw OnboardingException::onboardingAlreadyCompleted();
        }

        $onboardingProfile = $this->onboardingRepository->findOnboardingProfileByUserId($user->id);

        DB::transaction(function () use ($user , $onboardingProfile, $interestIds){

            $this->onboardingRepository->deleteUserInterestSelections($user->id);

            $rows = [];
            foreach ($interestIds as $index => $interestId)
            {
                $rows[] = [
                    'user_id' => $user->id,
                    'interest_id' => $interestId,
                    'slot_no' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $this->onboardingRepository->createUserInterestSelections($rows);

            $this->onboardingRepository->updateOnboardingProfile($onboardingProfile, [
                'last_completed_step' => 6,
            ]);

            $this->onboardingRepository->updateUserOnboardingCompletedAt($user->id,);
        });

        return [
            'onboarding_profile' => [
                'user_id' => $user->id,
                'interest_ids' => $interestIds,
                'interests_count' => count($interestIds)
            ]
        ];
    }

    public function getInterestCategoriesWithInterests():array
    {
        $categories = $this->onboardingRepository->getInterestCategoriesWithInterests();

        return $categories->map(function($category){
            return [
                'title' => $category->title,
                'interests' => $category->interests->map(function($interest){
                    return [
                      'id' => $interest->id,
                      'name' => $interest->name,
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();
    }

    public function getOnboardingProgressPreview(string $email): array
    {
        $user = $this->onboardingRepository->findUserByEmail($email);

        $onboardingProfile = $this->onboardingRepository->findOnboardingProfileByUserId($user->id);
        $userProfile = $this->onboardingRepository->findUserProfileByUserId($user->id);

        return [
            'discovery_source' => $onboardingProfile?->discovery_source,
            'education_level' => $onboardingProfile?->education_level,
            'governorate' => $userProfile->governorate
        ];
    }
}
