<?php

namespace App\Exceptions\Api;

class ProfileException extends ApiException
{
    public static function cannotViewOwnProfile(): self
    {
        return new self(
            title: '! لا يمكن تنفيذ العملية',
            message: 'هذا المسار مخصص لعرض ملف مستخدم معين وليس ملفك أنت',
            status: 403
        );
    }

    public static function profileNotFound(): self
    {
        return new self(
            title: '! فشل جلب الملف الشخصي',
            message: 'لم يتم العثور على بيانات الملف الشخصي لهذا المستخدم',
            status: 404
        );
    }
    public static function invalidAcademicLevelTransition(): self
    {
        return new self(
            title: '! فشل تعديل المستوى الدراسي',
            message: 'لا يمكنك النزول في المستوى الدراسي أو القفز على مستوى دراسي',
            status: 422
        );
    }

    public static function invalidSchoolStageTransition(): self
    {
        return new self(
            title: '! فشل تعديل المرحلة الدراسية',
            message: 'لا يمكنك النزول في المرحلة الدراسية',
            status: 422
        );
    }

    public static function schoolStageRequired(): self
    {
        return new self(
            title: '! فشل تعديل المعلومات الدراسية',
            message: 'المرحلة الدراسية مطلوبة عند اختيار مستوى المدرسة',
            status: 422
        );
    }

    public static function universityInformationRequired(): self
    {
        return new self(
            title: '! فشل تعديل المعلومات الدراسية',
            message: 'اسم الجامعة والقسم والسنة الدراسية مطلوبة لهذا المستوى الدراسي',
            status: 422
        );
    }

    public static function graduateInformationRequired(): self
    {
        return new self(
            title: '! فشل تعديل المعلومات الدراسية',
            message: 'اسم الجامعة والقسم مطلوبان لهذا المستوى الدراسي',
            status: 422
        );
    }

    public static function cannotSendVerificationRequestAfterApproval(): self
    {
        return new self(
            title: '! فشل إرسال طلب التوثيق',
            message: 'لا يمكنك إرسال طلب توثيق أكاديمي جديد لأن مستواك الأكاديمي موثق بالفعل',
            status: 409
        );
    }

    public static function pendingAcademicVerificationRequestExists(): self
    {
        return new self(
            title: '! فشل إرسال طلب التوثيق',
            message: 'لديك طلب توثيق أكاديمي قيد المراجعة بالفعل',
            status: 409
        );
    }
}
