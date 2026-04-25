<?php

namespace App\Services\TestDiscovery;

use App\Repositories\TestDiscovery\UserDiscoveryProfileRepository;
use App\Services\TestDiscovery\DTO\UserDiscoveryProfileData;
use App\Services\TestDiscovery\Resolvers\UserInterestWeightResolver;
use App\Services\TestDiscovery\Resolvers\UserTargetLevelPreferenceResolver;

final class UserDiscoveryProfileService
{

    public function __construct(
        private readonly UserDiscoveryProfileRepository $userDiscoveryProfileRepository,
        private readonly UserInterestWeightResolver $userInterestWeightResolver,
        private readonly UserTargetLevelPreferenceResolver $userTargetLevelPreferenceResolver,
    )
    {}

    /**
     هذا هو التابع الرئيسي الذي سيستعمله نظام التوصية
     وظيفته:
     1) جلب البيانات الخام للمستخدم
     2) تحويل الاهتمامات إلى أوزان
     3) اشتقاق target level preferences
     4) بناء الكائن النهائي الذي سيفهمه محرك التوصية
     */

    public function buildForUser(int $userId): UserDiscoveryProfileData
    {
        $rawData = $this->userDiscoveryProfileRepository->findRawDiscoveryDataByUserId($userId);

        $weightedInterests = $this->userInterestWeightResolver->resolve($rawData->interestSelections);

        $targetLevelPreference = $this->userTargetLevelPreferenceResolver->resolve($rawData);

        return new UserDiscoveryProfileData(
            userId: $rawData->userId,
            educationLevel: $rawData->educationLevel,
            schoolStage: $rawData->schoolStage,
            universityName: $rawData->universityName,
            universityDepartment: $rawData->universityDepartment,
            universityYear: $rawData->universityYear,
            interestIds: $rawData->interestIds(),
            weightedInterests: $weightedInterests,
            targetLevelPreference: $targetLevelPreference,
        );
    }
}
