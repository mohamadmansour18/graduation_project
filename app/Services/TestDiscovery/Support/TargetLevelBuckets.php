<?php

namespace App\Services\TestDiscovery\Support;

final class TargetLevelBuckets
{
    public const GENERAL_INFO = 'معلومات عامة';

    public const MASTER = 'ماجستير';
    public const DOCTORATE = 'دكتوراه';

    public const UNIVERSITY_YEAR_1 = 'سنة اولى جامعة';
    public const UNIVERSITY_YEAR_2 = 'سنة ثانية جامعة';
    public const UNIVERSITY_YEAR_3 = 'سنة ثالثة جامعة';
    public const UNIVERSITY_YEAR_4 = 'سنة رابعة جامعة';
    public const UNIVERSITY_YEAR_5 = 'سنة خامسة جامعة';
    public const UNIVERSITY_YEAR_6 = 'سنة سادسة جامعة';

    public const INSTITUTE_YEAR_1 = 'سنة اولى معهد';
    public const INSTITUTE_YEAR_2 = 'سنة ثانية معهد';
    public const INSTITUTE_YEAR_3 = 'سنة ثالثة معهد';

    public const SCHOOL_GRADE_1 = 'الصف الأول';
    public const SCHOOL_GRADE_2 = 'الصف الثاني';
    public const SCHOOL_GRADE_3 = 'الصف الثالث';
    public const SCHOOL_GRADE_4 = 'الصف الرابع';
    public const SCHOOL_GRADE_5 = 'الصف الخامس';
    public const SCHOOL_GRADE_6 = 'الصف السادس';
    public const SCHOOL_GRADE_7 = 'الصف السابع';
    public const SCHOOL_GRADE_8 = 'الصف الثامن';
    public const SCHOOL_GRADE_9 = 'الصف التاسع';
    public const SCHOOL_GRADE_10 = 'الصف العاشر';
    public const SCHOOL_GRADE_11 = 'الصف الحادي عشر';
    public const BACCALAUREATE = 'البكلوريا';

    /**
     * جميع مستويات المرحلة الابتدائية.
     */
    public static function primarySchoolLevels(): array
    {
        return [
            self::SCHOOL_GRADE_1,
            self::SCHOOL_GRADE_2,
            self::SCHOOL_GRADE_3,
            self::SCHOOL_GRADE_4,
            self::SCHOOL_GRADE_5,
            self::SCHOOL_GRADE_6,
        ];
    }

    /**
     * جميع مستويات المرحلة الإعدادية.
     */
    public static function middleSchoolLevels(): array
    {
        return [
            self::SCHOOL_GRADE_7,
            self::SCHOOL_GRADE_8,
            self::SCHOOL_GRADE_9,
        ];
    }

    /**
     * جميع مستويات المرحلة الثانوية.
     */
    public static function highSchoolLevels(): array
    {
        return [
            self::SCHOOL_GRADE_10,
            self::SCHOOL_GRADE_11,
            self::BACCALAUREATE,
        ];
    }

    /**
     * جميع الصفوف المدرسية كلها.
     */
    public static function allSchoolLevels(): array
    {
        return array_merge(
            self::primarySchoolLevels(),
            self::middleSchoolLevels(),
            self::highSchoolLevels(),
        );
    }

    /**
     * جميع السنوات الجامعية.
     */
    public static function allUniversityLevels(): array
    {
        return [
            self::UNIVERSITY_YEAR_1,
            self::UNIVERSITY_YEAR_2,
            self::UNIVERSITY_YEAR_3,
            self::UNIVERSITY_YEAR_4,
            self::UNIVERSITY_YEAR_5,
            self::UNIVERSITY_YEAR_6,
        ];
    }

    /**
     * السنوات الجامعية المتقدمة
     * سنستخدمها لاحقًا في حالة خريج
     */
    public static function advancedUniversityLevels(): array
    {
        return [
            self::UNIVERSITY_YEAR_3,
            self::UNIVERSITY_YEAR_4,
            self::UNIVERSITY_YEAR_5,
            self::UNIVERSITY_YEAR_6,
        ];
    }

    /**
     * السنوات الجامعية المبكرة
     * سنستخدمها كـ secondary في بعض الحالات
     */
    public static function earlyUniversityLevels(): array
    {
        return [
            self::UNIVERSITY_YEAR_1,
            self::UNIVERSITY_YEAR_2,
        ];
    }

    /**
     * جميع سنوات المعهد
     * لن نستعملها بقوة في المرحلة الأولى لأن onboarding الحالي لا يميز المعهد صراحة
     */
    public static function allInstituteLevels(): array
    {
        return [
            self::INSTITUTE_YEAR_1,
            self::INSTITUTE_YEAR_2,
            self::INSTITUTE_YEAR_3,
        ];
    }

    /**
     * مستوى المعلومات العامة.
     */
    public static function generalInfoLevels(): array
    {
        return [self::GENERAL_INFO];
    }

    /**
     * هذا التابع يعيد target level الجامعي المقابل لرقم السنة
     */
    public static function universityLevelByYear(?int $year): ?string
    {
        return match ($year) {
            1 => self::UNIVERSITY_YEAR_1,
            2 => self::UNIVERSITY_YEAR_2,
            3 => self::UNIVERSITY_YEAR_3,
            4 => self::UNIVERSITY_YEAR_4,
            5 => self::UNIVERSITY_YEAR_5,
            6 => self::UNIVERSITY_YEAR_6,
            default => null,
        };
    }
}
