<?php

namespace App\Services\TestDiscovery\Resolvers;

use App\Services\TestDiscovery\DTO\TargetLevelPreferenceData;
use App\Services\TestDiscovery\DTO\UserDiscoveryRawData;
use App\Services\TestDiscovery\Support\TargetLevelBuckets;

final class UserTargetLevelPreferenceResolver
{
    public function resolve(UserDiscoveryRawData $rawData): TargetLevelPreferenceData
    {
        return match ($rawData->educationLevel) {
            'مدرسة' => $this->resolveSchoolLevels($rawData),
            'جامعة' => $this->resolveUniversityLevels($rawData),
            'ماجستير' => $this->resolveMasterLevels(),
            'دكتوراه' => $this->resolveDoctorateLevels(),
            'خريج' => $this->resolveGraduateLevels(),
            default => $this->resolveUnknownLevels(),
        };
    }

    /**
     * حالة المدرسة:
     * - نعتمد على school_stage لأننا لا نملك الصف التفصيلي حاليًا
     * - لذلك نرجع "مجموعة صفوف" وليس صفًا واحدًا
     */
    private function resolveSchoolLevels(UserDiscoveryRawData $rawData): TargetLevelPreferenceData
    {

        return match ($rawData->schoolStage) {
            'ابتدائي' => new TargetLevelPreferenceData(
                primaryLevels: TargetLevelBuckets::primarySchoolLevels(),
                secondaryLevels: [],
                broadLevels: TargetLevelBuckets::generalInfoLevels(),
                confidence: 'high',
                reason: 'school_stage_matched_primary'
            ),

            'اعدادي' => new TargetLevelPreferenceData(
                primaryLevels: TargetLevelBuckets::middleSchoolLevels(),
                secondaryLevels: [],
                broadLevels: TargetLevelBuckets::generalInfoLevels(),
                confidence: 'high',
                reason: 'school_stage_matched_middle'
            ),

            'ثانوي' => new TargetLevelPreferenceData(
                primaryLevels: TargetLevelBuckets::highSchoolLevels(),
                secondaryLevels: [],
                broadLevels: TargetLevelBuckets::generalInfoLevels(),
                confidence: 'high',
                reason: 'school_stage_matched_high'
            ),

            default => new TargetLevelPreferenceData(
                primaryLevels: TargetLevelBuckets::allSchoolLevels(),
                secondaryLevels: [],
                broadLevels: TargetLevelBuckets::generalInfoLevels(),
                confidence: 'medium',
                reason: 'school_stage_missing'
            ),
        };
    }

    /**
     * حالة الجامعة:
     * - نعتمد أساسًا على university_year
     * - نرجع السنة الحالية كـ primary
     * - ونرجع السنوات المجاورة كـ secondary
     */
    private function resolveUniversityLevels(UserDiscoveryRawData $rawData): TargetLevelPreferenceData
    {
        $year = $rawData->universityYear;
        $primaryLevel = TargetLevelBuckets::universityLevelByYear($year);

        if ($primaryLevel === null) {
            return new TargetLevelPreferenceData(
                primaryLevels: TargetLevelBuckets::allUniversityLevels(),
                secondaryLevels: [],
                broadLevels: TargetLevelBuckets::generalInfoLevels(),
                confidence: 'medium',
                reason: 'university_year_missing'
            );
        }

        $secondaryLevels = [];

        $previousLevel = TargetLevelBuckets::universityLevelByYear($year - 1);
        $nextLevel = TargetLevelBuckets::universityLevelByYear($year + 1);

        if ($previousLevel !== null) {
            $secondaryLevels[] = $previousLevel;
        }

        if ($nextLevel !== null) {
            $secondaryLevels[] = $nextLevel;
        }

        return new TargetLevelPreferenceData(
            primaryLevels: [$primaryLevel],
            secondaryLevels: $secondaryLevels,
            broadLevels: TargetLevelBuckets::generalInfoLevels(),
            confidence: 'high',
            reason: 'university_year_matched'
        );
    }


    /**
     * حالة الماجستير:
     * - مطابقة مباشرة وواضحة
     */
    private function resolveMasterLevels(): TargetLevelPreferenceData
    {
        return new TargetLevelPreferenceData(
            primaryLevels: [TargetLevelBuckets::MASTER],
            secondaryLevels: [],
            broadLevels: TargetLevelBuckets::generalInfoLevels(),
            confidence: 'high',
            reason: 'master_direct_match'
        );
    }


    /**
     * حالة الدكتوراه:
     * - primary = دكتوراه
     * - secondary = ماجستير
     *
     * السبب:
     * طالب الدكتوراه غالبًا ما يزال يستطيع الاستفادة من محتوى الماجستير.
     */
    private function resolveDoctorateLevels(): TargetLevelPreferenceData
    {
        return new TargetLevelPreferenceData(
            primaryLevels: [TargetLevelBuckets::DOCTORATE],
            secondaryLevels: [TargetLevelBuckets::MASTER],
            broadLevels: TargetLevelBuckets::generalInfoLevels(),
            confidence: 'high',
            reason: 'doctorate_direct_match'
        );
    }


    /**
     * حالة الخريج:
     * - لا نملك سنة دراسية حية الآن
     * - لذلك نعطيه سنوات جامعية متقدمة كـ primary
     * - وسنوات مبكرة + ماجستير كـ secondary
     */
    private function resolveGraduateLevels(): TargetLevelPreferenceData
    {
        return new TargetLevelPreferenceData(
            primaryLevels: TargetLevelBuckets::advancedUniversityLevels(),
            secondaryLevels: array_merge(
                TargetLevelBuckets::earlyUniversityLevels(),
                [TargetLevelBuckets::MASTER]
            ),
            broadLevels: TargetLevelBuckets::generalInfoLevels(),
            confidence: 'medium',
            reason: 'graduate_broad_mapping'
        );
    }


    /**
     * إذا كانت البيانات ناقصة جدًا أو education level غير متوقع،
     * لا نكسر النظام.
     *
     * نرجع broad fallback فقط.
     */
    private function resolveUnknownLevels(): TargetLevelPreferenceData
    {
        return new TargetLevelPreferenceData(
            primaryLevels: [],
            secondaryLevels: [],
            broadLevels: TargetLevelBuckets::generalInfoLevels(),
            confidence: 'low',
            reason: 'unknown_education_level'
        );
    }
}
