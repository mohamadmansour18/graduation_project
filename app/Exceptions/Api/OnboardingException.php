<?php

namespace App\Exceptions\Api;

class OnboardingException extends ApiException
{
    public static function emailNotVerifiedForOnboarding(): self
    {
        return new self(
            title: '! البريد الإلكتروني غير مؤكد',
            message: 'لا يمكن متابعة إدخال معلوماتك قبل تأكيد البريد الإلكتروني',
            status: 403
        );
    }

    public static function invalidEducationPath(string $level = 'مدرسة'): self
    {
        return new self(
            title: '! المسار الدراسي غير صحيح',
            message: "لا يمكن حفظ المرحلة الدراسية لأن المستوى الدراسي الحالي ليس مسار $level",
            status: 422
        );
    }

    public static function pendingAcademicVerificationRequestExists(): self
    {
        return new self(
            title: 'طلب توثيق قيد المراجعة',
            message: 'لديك طلب توثيق أكاديمي قيد المراجعة بالفعل، ولا يمكنك تقديم طلب جديد الآن',
            status: 422
        );
    }

    public static function invalidEducationPathForGraduateAcademicProfile(): self
    {
        return new self(
            title: 'المسار الدراسي غير صحيح',
            message: 'لا يمكن حفظ هذه البيانات لأن المستوى الدراسي الحالي لا ينتمي إلى مسار الخريج أو الماجستير أو الدكتوراه',
            status: 422
        );
    }

    public static function onboardingAlreadyCompleted(): self
    {
        return new self(
            title: 'تم إكمال التهيئة مسبقًا',
            message: 'لا يمكن تعديل الاهتمامات العلمية بعد إكمال التهيئة',
            status: 422
        );
    }
}
