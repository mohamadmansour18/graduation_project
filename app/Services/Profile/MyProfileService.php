<?php

namespace App\Services\Profile;

use App\Exceptions\Api\ProfileException;
use App\Repositories\Profile\MyProfileRepository;
use App\Services\Cache\CacheKeys;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MyProfileService
{
    private const string LEVEL_SCHOOL = 'مدرسة';
    private const string LEVEL_UNIVERSITY = 'جامعة';
    private const string LEVEL_GRADUATE = 'خريج';
    private const string LEVEL_MASTER = 'ماجستير';
    private const string LEVEL_PHD = 'دكتوراه';

    private const string SCHOOL_STAGE_ELEMENTARY = 'ابتدائي';
    private const string SCHOOL_STAGE_PREPARATORY = 'اعدادي';
    private const string SCHOOL_STAGE_SECONDARY = 'ثانوي';

    private const array EDUCATION_LEVEL_ORDER = [
        self::LEVEL_SCHOOL => 1,
        self::LEVEL_UNIVERSITY => 2,
        self::LEVEL_GRADUATE => 3,
        self::LEVEL_MASTER => 4,
        self::LEVEL_PHD => 5,
    ];

    private const array SCHOOL_STAGE_ORDER = [
        self::SCHOOL_STAGE_ELEMENTARY => 1,
        self::SCHOOL_STAGE_PREPARATORY => 2,
        self::SCHOOL_STAGE_SECONDARY => 3,
    ];

    public function __construct(
        private readonly MyProfileRepository $myProfileRepository
    ) {}

    public function getMyBasicInfo(int $userId , int $viewerId): array
    {
        if($userId !== $viewerId)
        {
            throw ProfileException::cannotViewOwnProfile();
        }

        $cached = Cache::tags(CacheKeys::myBasicProfileInfoTags($userId))
            ->remember(
                CacheKeys::myBasicProfileInfo($userId),
                now()->addMinutes(10),
                function () use ($userId): array {
                    $profile = $this->myProfileRepository->getBasicInfoByUserId($userId);

                    if (! $profile) {
                        throw ProfileException::profileNotFound();
                    }

                    return $profile;
                }
            );

        if (! is_array($cached)) {
            Cache::tags(CacheKeys::myBasicProfileInfoTags($userId))->forget(CacheKeys::myBasicProfileInfo($userId));

            throw ProfileException::profileNotFound();
        }

        return $cached;
    }

    public function updatePersonalInformation(int $userId, array $data): void
    {
        DB::transaction(function () use ($userId, $data) {
            $this->myProfileRepository->ensureUserProfileRow($userId);

            $userData = [];
            $profileData = [];

            if (array_key_exists('name', $data)) {
                $userData['name'] = $data['name'];
            }

            if (array_key_exists('gender', $data)) {
                $userData['gender'] = $data['gender'];
            }

            if (array_key_exists('governorate', $data)) {
                $profileData['governorate'] = $data['governorate'];
            }

            if (array_key_exists('phone', $data)) {
                $profileData['phone'] = $data['phone'];
            }

            if (array_key_exists('birth_date', $data)) {
                $profileData['birth_date'] = $data['birth_date'];
            }

            $this->myProfileRepository->updateUserPersonalData($userId, $userData);
            $this->myProfileRepository->updateUserProfileData($userId, $profileData);
        });

        CacheKeys::clearMyBasicProfileInfo($userId);
    }

    public function updateAcademicInformation(int $userId, array $data, ?UploadedFile $certificateImage = null, ?UploadedFile $identityImage = null): void
    {
        DB::transaction(function () use ($userId, $data, $certificateImage, $identityImage) {
            $current = $this->myProfileRepository->getAcademicSnapshotForUpdate($userId);

            if (! $current) {
                throw ProfileException::profileNotFound();
            }

            $this->ensureValidEducationLevelTransition(
                currentLevel: $current['education_level'],
                targetLevel: $data['education_level']
            );

            if ($data['education_level'] === self::LEVEL_SCHOOL) {
                $this->updateSchoolAcademicInformation(
                    userId: $userId,
                    currentSchoolStage: $current['school_stage'] ?? null,
                    data: $data
                );

                return;
            }

            if ($data['education_level'] === self::LEVEL_UNIVERSITY) {
                $this->updateUniversityAcademicInformation(
                    userId: $userId,
                    data: $data
                );

                return;
            }

            $this->updateGraduateOrHigherAcademicInformation(
                userId: $userId,
                targetLevel: $data['education_level'],
                data: $data,
                certificateImage: $certificateImage,
                identityImage: $identityImage
            );
        });

        CacheKeys::clearMyBasicProfileInfo($userId);
    }

    public function updateScientificInterests(int $userId, array $interestIds): void
    {
        DB::transaction(function () use ($userId, $interestIds) {
            $this->myProfileRepository->replaceScientificInterests(
                userId: $userId,
                interestIds: $interestIds
            );
        });

        CacheKeys::clearMyBasicProfileInfo($userId);
    }

    private function updateSchoolAcademicInformation(int $userId, ?string $currentSchoolStage, array $data): void
    {
        $targetSchoolStage = $data['school_stage'] ?? null;

        if (! $targetSchoolStage) {
            throw ProfileException::schoolStageRequired();
        }

        if ($currentSchoolStage) {
            $this->ensureValidSchoolStageTransition(
                currentSchoolStage: $currentSchoolStage,
                targetSchoolStage: $targetSchoolStage
            );
        }

        $this->myProfileRepository->updateEducationLevel($userId, self::LEVEL_SCHOOL);
        $this->myProfileRepository->upsertSchoolProfile($userId, $targetSchoolStage);
        $this->myProfileRepository->deleteUniversityProfile($userId);
    }

    private function updateUniversityAcademicInformation(int $userId, array $data): void
    {
        $universityName = $data['university_name'] ?? null;
        $department = $data['department'] ?? null;
        $universityYear = $data['university_year'] ?? null;

        if (! $universityName || ! $department || ! $universityYear) {
            throw ProfileException::universityInformationRequired();
        }

        $this->myProfileRepository->updateEducationLevel($userId, self::LEVEL_UNIVERSITY);

        $this->myProfileRepository->upsertUniversityProfile(
            userId: $userId,
            universityName: $universityName,
            department: $department,
            universityYear: $universityYear
        );

        $this->myProfileRepository->deleteSchoolProfile($userId);
    }

    private function updateGraduateOrHigherAcademicInformation(int $userId, string $targetLevel, array $data, ?UploadedFile $certificateImage, ?UploadedFile $identityImage): void
    {
        $hasApprovedRequest = $this->myProfileRepository
            ->hasApprovedAcademicVerificationRequest($userId);

        if ($hasApprovedRequest) {
            throw ProfileException::cannotEditApprovedAcademicInformation();
        }

        $universityName = $data['university_name'] ?? null;
        $department = $data['department'] ?? null;

        if (! $universityName || ! $department) {
            throw ProfileException::graduateInformationRequired();
        }

        $this->myProfileRepository->updateEducationLevel($userId, $targetLevel);

        $this->myProfileRepository->upsertUniversityProfile(
            userId: $userId,
            universityName: $universityName,
            department: $department,
            universityYear: $data['university_year'] ?? null
        );

        $this->myProfileRepository->deleteSchoolProfile($userId);

        if ($certificateImage || $identityImage) {
            $this->createAcademicVerificationRequestWithAssets(
                userId: $userId,
                certificateImage: $certificateImage,
                identityImage: $identityImage
            );
        }
    }

    private function createAcademicVerificationRequestWithAssets(int $userId, ?UploadedFile $certificateImage, ?UploadedFile $identityImage): void
    {
        if (! $certificateImage || ! $identityImage) {
            throw ProfileException::graduateInformationRequired();
        }

        if ($this->myProfileRepository->hasPendingAcademicVerificationRequest($userId)) {
            throw ProfileException::pendingAcademicVerificationRequestExists();
        }

        $certificatePath = $this->storeEncryptedAcademicAsset(
            file: $certificateImage,
            directory: 'academic-verification/certificates'
        );

        $identityPath = $this->storeEncryptedAcademicAsset(
            file: $identityImage,
            directory: 'academic-verification/identities'
        );

        $verificationRequestId = $this->myProfileRepository
            ->createAcademicVerificationRequest($userId);

        $this->myProfileRepository->createAcademicVerificationAsset(
            verificationRequestId: $verificationRequestId,
            assetType: 'certificate',
            storagePath: $certificatePath,
            originalName: $certificateImage->getClientOriginalName(),
            mimeType: $certificateImage->getClientMimeType()
        );

        $this->myProfileRepository->createAcademicVerificationAsset(
            verificationRequestId: $verificationRequestId,
            assetType: 'identity',
            storagePath: $identityPath,
            originalName: $identityImage->getClientOriginalName(),
            mimeType: $identityImage->getClientMimeType()
        );
    }

    private function storeEncryptedAcademicAsset(UploadedFile $file, string $directory): string
    {
        $encryptedContent = Crypt::encrypt($file->get());

        $fileName = Str::uuid()->toString() . '.enc';

        $path = $directory . '/' . $fileName;

        Storage::disk('local')->put($path, $encryptedContent);

        return $path;
    }

    private function ensureValidEducationLevelTransition(string $currentLevel, string $targetLevel): void
    {
        $currentOrder = self::EDUCATION_LEVEL_ORDER[$currentLevel] ?? null;
        $targetOrder = self::EDUCATION_LEVEL_ORDER[$targetLevel] ?? null;

        if (! $currentOrder || ! $targetOrder) {
            throw ProfileException::invalidAcademicLevelTransition();
        }

        $difference = $targetOrder - $currentOrder;

        if ($difference < 0 || $difference > 1) {
            throw ProfileException::invalidAcademicLevelTransition();
        }
    }

    private function ensureValidSchoolStageTransition(string $currentSchoolStage, string $targetSchoolStage): void
    {
        $currentOrder = self::SCHOOL_STAGE_ORDER[$currentSchoolStage] ?? null;
        $targetOrder = self::SCHOOL_STAGE_ORDER[$targetSchoolStage] ?? null;

        if (! $currentOrder || ! $targetOrder) {
            throw ProfileException::invalidSchoolStageTransition();
        }

        if ($targetOrder < $currentOrder) {
            throw ProfileException::invalidSchoolStageTransition();
        }
    }

}
