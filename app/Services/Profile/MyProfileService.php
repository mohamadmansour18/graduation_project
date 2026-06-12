<?php

namespace App\Services\Profile;

use App\Enums\AcademicAssetType;
use App\Exceptions\Api\ProfileException;
use App\Exceptions\Api\PublicProfileException;
use App\Repositories\Profile\MyProfileRepository;
use App\Services\Cache\CacheKeys;
use Illuminate\Contracts\Pagination\CursorPaginator;
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

    private const string PHOTO_TYPE_AVATAR = 'avatar';
    private const string PHOTO_TYPE_COVER = 'cover';
    private const string PROFILE_PHOTO_DISK = 'local';
    private const string AVATAR_DIRECTORY = 'users-photo/profile';
    private const string COVER_DIRECTORY = 'users-photo/cover';
    private const string DEFAULT_AVATAR_PATH = 'defaults/default-avatar.svg';
    private const string DEFAULT_COVER_PATH = 'defaults/default-cover.svg';
    private const string DEFAULT_PHOTO_DISK = 'public';

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
                now()->addDays(10),
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

    public function updatePersonalInformation(int $userId, array $data , int $viewerId): void
    {
        if($userId !== $viewerId)
        {
            throw ProfileException::cannotViewOwnProfile();
        }

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

    public function updateAcademicInformation(int $userId, array $data, int $viewerId , ?UploadedFile $certificateImage = null, ?UploadedFile $identityImage = null ): void
    {
        if($userId !== $viewerId)
        {
            throw ProfileException::cannotViewOwnProfile();
        }

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

    public function updateScientificInterests(int $userId, array $interestIds , int $viewerId): void
    {
        if($userId !== $viewerId)
        {
            throw ProfileException::cannotViewOwnProfile();
        }

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
        $isAcademicallyVerified = $this->myProfileRepository->isUserAcademicallyVerified($userId);

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
            universityYear: null
        );

        if ($certificateImage || $identityImage) {
            if ($isAcademicallyVerified) {
                throw ProfileException::cannotSendVerificationRequestAfterApproval();
            }

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

        $verificationRequestId = $this->myProfileRepository->createAcademicVerificationRequest($userId);

        $this->myProfileRepository->createAcademicVerificationAsset(
            verificationRequestId: $verificationRequestId,
            assetType: AcademicAssetType::University_Certificate->value,
            storagePath: $certificatePath,
            originalName: $certificateImage->getClientOriginalName(),
            mimeType: $certificateImage->getClientMimeType()
        );

        $this->myProfileRepository->createAcademicVerificationAsset(
            verificationRequestId: $verificationRequestId,
            assetType: AcademicAssetType::Identity_Card->value,
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

    public function updatePhoto(int $userId, string $type, UploadedFile $photo , int $viewerId): void
    {

        if($userId !== $viewerId)
        {
            throw ProfileException::cannotViewOwnProfile();
        }

        $oldDisk = null;
        $oldPath = null;
        $newPath = null;

        DB::transaction(function () use ($userId, $type, $photo, &$oldDisk, &$oldPath, &$newPath) {
            $this->myProfileRepository->ensureUserProfileRow($userId);

            $profile = $this->myProfileRepository->getProfilePhotoDataForUpdate($userId);

            if (! $profile) {
                throw ProfileException::profileNotFound();
            }

            if ($type === self::PHOTO_TYPE_AVATAR) {
                $oldDisk = $profile['avatar_disk'] ?? null;
                $oldPath = $profile['avatar_path'] ?? null;

                $newPath = $this->storeProfilePhoto($photo, self::AVATAR_DIRECTORY);

                $this->myProfileRepository->updateAvatar(
                    userId: $userId,
                    disk: self::PROFILE_PHOTO_DISK,
                    path: $newPath
                );

                return;
            }

            if ($type === self::PHOTO_TYPE_COVER) {
                $oldDisk = $profile['cover_disk'] ?? null;
                $oldPath = $profile['cover_path'] ?? null;

                $newPath = $this->storeProfilePhoto($photo, self::COVER_DIRECTORY);

                $this->myProfileRepository->updateCover(
                    userId: $userId,
                    disk: self::PROFILE_PHOTO_DISK,
                    path: $newPath
                );

                return;
            }
        });

        $this->deleteOldPhotoIfExists($oldDisk, $oldPath);

        CacheKeys::clearMyBasicProfileInfo($userId);
    }

    public function deletePhoto(int $userId, string $type , int $viewerId): string
    {
        if($userId !== $viewerId)
        {
            throw ProfileException::cannotViewOwnProfile();
        }

        $oldDisk = null;
        $oldPath = null;
        $defaultUrl = null;

        DB::transaction(function () use ($userId, $type, &$oldDisk, &$oldPath, &$defaultUrl) {
            $this->myProfileRepository->ensureUserProfileRow($userId);

            $profile = $this->myProfileRepository->getProfilePhotoDataForUpdate($userId);

            if (! $profile) {
                throw ProfileException::profileNotFound();
            }

            if ($type === self::PHOTO_TYPE_AVATAR) {
                $oldDisk = $profile['avatar_disk'] ?? null;
                $oldPath = $profile['avatar_path'] ?? null;

                $this->myProfileRepository->clearAvatar($userId);

                $defaultUrl = Storage::disk(self::DEFAULT_PHOTO_DISK)->url(self::DEFAULT_AVATAR_PATH);

                return;
            }

            if ($type === self::PHOTO_TYPE_COVER) {
                $oldDisk = $profile['cover_disk'] ?? null;
                $oldPath = $profile['cover_path'] ?? null;

                $this->myProfileRepository->clearCover($userId);

                $defaultUrl = Storage::disk(self::DEFAULT_PHOTO_DISK)->url(self::DEFAULT_COVER_PATH);

                return;
            }
        });

        $this->deleteOldPhotoIfExists($oldDisk, $oldPath);

        CacheKeys::clearMyBasicProfileInfo($userId);

        return $defaultUrl;
    }

    private function storeProfilePhoto(UploadedFile $photo, string $directory): string
    {
        $extension = $photo->getClientOriginalExtension();

        $fileName = Str::uuid()->toString() . '.' . $extension;

        return $photo->storeAs(
            path: $directory,
            name: $fileName,
            options: self::PROFILE_PHOTO_DISK
        );
    }

    private function deleteOldPhotoIfExists(?string $disk, ?string $path): void
    {
        if (! $disk || ! $path) {
            return;
        }

        if ($disk !== self::PROFILE_PHOTO_DISK) {
            return;
        }

        Storage::disk($disk)->delete($path);
    }

    public function getMyCreatedTests(int $userId , int $viewerId , string $tab = 'public', int $perPage = 20 ): CursorPaginator
    {
        if($userId !== $viewerId)
        {
            throw ProfileException::cannotViewOwnProfile();
        }

        return $this->myProfileRepository->getMyCreatedTestsByTab(
            userId: $userId,
            tab: $tab,
            perPage: $perPage
        );
    }

    public function getMyLibraryMaterials(int $userId , int $viewerId, string $tab = 'latest', int $perPage = 10): CursorPaginator
    {
        if($userId !== $viewerId)
        {
            throw ProfileException::cannotViewOwnProfile();
        }

        return $this->myProfileRepository->getMyLibraryMaterialsByTab(
            userId: $userId,
            tab: $tab,
            perPage: $perPage
        );
    }

    public function getMyFolders(int $userId , int $viewerId , string $tab , int $perPage): CursorPaginator
    {
        if ($viewerId !== $userId) {
            throw ProfileException::cannotViewOwnProfile();
        }

        return $this->myProfileRepository->cursorPaginateUserFolders(
            userId: $userId,
            tab: $tab,
            perPage: $perPage
        );
    }

    public function getBookmarks(int $userId, string $tab, int $perPage): CursorPaginator
    {
        return match ($tab) {
            'materials' => $this->myProfileRepository->cursorPaginateBookmarkedMaterials($userId, $perPage),
            'folders' => $this->myProfileRepository->cursorPaginateBookmarkedFolders($userId, $perPage),
            default => $this->myProfileRepository->cursorPaginateBookmarkedTests($userId, $perPage),
        };
    }
}
