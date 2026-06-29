<?php

namespace App\Exceptions\Api;

class StudyPlanException extends ApiException
{
    public static function maxActiveOrFuturePlansReached(): self
    {
        return new self(
            title: '! فشل إنشاء الخطة الدراسية',
            message: 'لا يمكنك امتلاك أكثر من خمس خطط دراسية حالية أو مستقبلية',
            status: 422
        );
    }

    public static function defaultFlagRequiredForFirstPlan(): self
    {
        return new self(
            title: '! فشل إنشاء الخطة الدراسية',
            message: 'يجب تحديد ما إذا كانت أول خطة دراسية ستكون الخطة الافتراضية',
            status: 422
        );
    }

    public static function firstPlanMustBeDefault(): self
    {
        return new self(
            title: '! فشل إنشاء الخطة الدراسية',
            message: 'أول خطة دراسية يجب أن تكون الخطة الافتراضية',
            status: 422
        );
    }

    public static function someSubjectsDoNotBelongToUser(): self
    {
        return new self(
            title: '! فشل إنشاء الخطة الدراسية',
            message: 'بعض المواد المختارة غير موجودة أو لا تعود لهذا المستخدم',
            status: 403
        );
    }

    public static function maxSubjectsReached(): self
    {
        return new self(
            title: '! فشل إنشاء المادة',
            message: 'لا يمكنك إنشاء أكثر من 50 مادة دراسية',
            status: 422
        );
    }

    public static function subjectAlreadyExists(): self
    {
        return new self(
            title: '! فشل إنشاء المادة',
            message: 'لديك مادة دراسية بنفس الاسم مسبقًا',
            status: 409
        );
    }

    public static function subjectNotFound(): self
    {
        return new self(
            title: '! فشل حذف المادة',
            message: 'المادة الدراسية غير موجودة',
            status: 404
        );
    }

    public static function planNotFound(): self
    {
        return new self(
            title: '! فشل جلب الخطة الدراسية',
            message: 'الخطة الدراسية غير موجودة',
            status: 404
        );
    }

    public static function invalidPlanDateRange(): self
    {
        return new self(
            title: '! فشل تعديل الخطة الدراسية',
            message: 'تاريخ نهاية الخطة يجب أن يكون بعد تاريخ البداية',
            status: 422
        );
    }

    public static function startDateCannotBePast(): self
    {
        return new self(
            title: '! فشل تعديل الخطة الدراسية',
            message: 'لا يمكن أن يكون تاريخ بداية الخطة أصغر من تاريخ اليوم الحالي',
            status: 422
        );
    }

    public static function invalidPlanDuration(): self
    {
        return new self(
            title: '! فشل تعديل الخطة الدراسية',
            message: 'مدة الخطة الدراسية يجب ألا تقل عن يوم واحد وألا تتجاوز 365 يومًا',
            status: 422
        );
    }

    public static function tasksOutsideNewPlanDateRange(): self
    {
        return new self(
            title: '! فشل تعديل الخطة الدراسية',
            message: 'لا يمكن تعديل تواريخ الخطة لأن بعض المهام الحالية ستصبح خارج حدود الخطة، يرجى تعديل تواريخ المهام أولًا',
            status: 422
        );
    }

    public static function dailyStudyHoursBreakExistingTasks(): self
    {
        return new self(
            title: '! فشل تعديل الخطة الدراسية',
            message: 'لا يمكن تقليل ساعات الدراسة اليومية لأن مجموع مهام أحد الأيام يتجاوز الحد الجديد',
            status: 422
        );
    }

    public static function alreadyDefaultPlan(): self
    {
        return new self(
            title: '! فشل تعديل الخطة الدراسية',
            message: 'هذه الخطة الدراسية هي بالفعل الخطة الافتراضية',
            status: 409
        );
    }

    public static function cannotUnsetDefaultPlan(): self
    {
        return new self(
            title: '! فشل تعديل الخطة الدراسية',
            message: 'لا يمكن إلغاء الخطة الافتراضية دون اختيار خطة أخرى كافتراضية',
            status: 422
        );
    }

    public static function cannotRemoveSubjectHasTasks(): self
    {
        return new self(
            title: '! فشل تعديل الخطة الدراسية',
            message: 'لا يمكن إزالة مادة من الخطة لأنها تحتوي على مهام، يرجى تعديل أو حذف مهام المادة أولًا',
            status: 422
        );
    }

    public static function someSubjectsDoNotBelongToUser2(): self
    {
        return new self(
            title: '! فشل تعديل الخطة الدراسية',
            message: 'بعض المواد المختارة غير موجودة أو لا تعود لهذا المستخدم',
            status: 403
        );
    }

    public static function subjectDoesNotBelongToPlan(): self
    {
        return new self(
            title: '! فشل إنشاء المهمة',
            message: 'المادة المختارة غير موجودة ضمن هذه الخطة الدراسية',
            status: 403
        );
    }

    public static function taskOutsidePlanDateRange(): self
    {
        return new self(
            title: '! فشل إنشاء المهمة',
            message: 'تاريخ المهمة يجب أن يكون ضمن الحدود الزمنية للخطة الدراسية',
            status: 422
        );
    }

    public static function dailyStudyLimitExceeded(string $date): self
    {
        return new self(
            title: '! فشل إنشاء المهمة',
            message: "لا يمكن إنشاء المهمة لأن مجموع مهام يوم {$date} يتجاوز الحد اليومي المسموح للدراسة",
            status: 422
        );
    }

    public static function recurrenceBreaksDailyLimit(string $date): self
    {
        return new self(
            title: '! فشل إنشاء المهمة',
            message: "لا يمكن تكرار المهمة لأن يوم {$date} تجاوز الحد اليومي المسموح للدراسة",
            status: 422
        );
    }

    public static function taskNotFound(): self
    {
        return new self(
            title: '! فشل العملية',
            message: 'المهمة غير موجودة',
            status: 404
        );
    }

    public static function invalidDateRange(): self
    {
        return new self(
            title: '! فشل تعديل المهمة',
            message: 'تاريخ انتهاء المهمة يجب أن يكون بعد أو يساوي تاريخ البداية',
            status: 422
        );
    }

    public static function taskStartDateCannotBePast(): self
    {
        return new self(
            title: '! فشل تعديل المهمة',
            message: 'لا يمكن أن يكون تاريخ بداية المهمة أصغر من تاريخ اليوم الحالي',
            status: 422
        );
    }

    public static function taskDurationRangeInvalid(): self
    {
        return new self(
            title: '! فشل تعديل المهمة',
            message: 'لا يمكن أن تمتد المهمة لأكثر من أسبوع واحد',
            status: 422
        );
    }

    public static function taskCrossesDayBoundary(): self
    {
        return new self(
            title: '! فشل تعديل المهمة',
            message: 'مدة المهمة مع وقت البداية يجب ألا تتجاوز نهاية اليوم',
            status: 422
        );
    }

    public static function subtaskDoesNotBelongToTask(): self
    {
        return new self(
            title: '! فشل تعديل المهمة',
            message: 'إحدى المهام الفرعية المرسلة لا تتبع هذه المهمة',
            status: 403
        );
    }
    public static function subtaskNotFound(): self
    {
        return new self(
            title: '! فشل العملية',
            message: 'المهمة الفرعية غير موجودة',
            status: 404
        );
    }
}
