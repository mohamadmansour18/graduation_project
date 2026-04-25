<?php

namespace App\Repositories\TestDiscovery;

use App\Services\TestDiscovery\DTO\UserDiscoveryRawData;
use Illuminate\Support\Facades\DB;

/**
 * هذا الـ repository مسؤول فقط عن جلب "البيانات الخام" للمستخدم
 * من قواعد بيانات الـ onboarding والاهتمامات.
 * مهم:
 * - لا يحسب أوزان
  * - لا يشتق target levels
  * - لا يفكر في ranking
  * هو فقط fetch layer
 */
class UserDiscoveryProfileRepository
{
    public function findRawDiscoveryDataByUserId(int $userId): UserDiscoveryRawData
    {
        $onboardingProfile = DB::table('user_onboarding_profiles')
            ->select([
                'user_id',
                'education_level',
            ])
            ->where('user_id', $userId)
            ->first();

        $schoolProfile = DB::table('user_school_profiles')
            ->select([
                'user_id',
                'school_stage',
            ])
            ->where('user_id', $userId)
            ->first();

        $universityProfile = DB::table('user_university_profiles')
            ->select([
                'user_id',
                'university_name',
                'department',
                'university_year',
            ])
            ->where('user_id', $userId)
            ->first();

        $interestSelections = DB::table('user_interest_selections')
            ->select([
                'interest_id',
                'slot_no',
            ])
            ->where('user_id', $userId)
            ->get()
            ->map(static fn ($row): array => [
                'interest_id' => (int) $row->interest_id,
                'slot_no' => isset($row->slot_no) ? (int) $row->slot_no : null,
            ])
            ->all();

        return new UserDiscoveryRawData(
            userId: $userId,
            educationLevel: $onboardingProfile->education_level ?? null,
            schoolStage: $schoolProfile->school_stage ?? null,
            universityName: $universityProfile->university_name ?? null,
            universityDepartment: $universityProfile->department ?? null,
            universityYear: isset($universityProfile->university_year) ? (int) $universityProfile->university_year : null,
            interestSelections: $interestSelections,
        );
    }
}
