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
}
