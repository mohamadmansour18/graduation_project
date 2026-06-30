<?php

namespace App\Exceptions\Api;

class SettingsException extends ApiException
{
    public static function pendingRequestAlreadyExists(): self
    {
        return new self(
            title: '! فشل إرسال الطلب',
            message: 'لديك طلب توثيق قيد المراجعة بالفعل',
            status: 409
        );
    }

    public static function alreadyVerified(): self
    {
        return new self(
            title: '! فشل إرسال الطلب',
            message: 'حسابك موثق أكاديمياً بالفعل',
            status: 409
        );
    }

    public static function approvedRequestRequired(): self
    {
        return new self(
            title: '! فشل تحديث الإعداد',
            message: 'يجب أن يكون لديك طلب توثيق مؤكد لتغيير ظهور الشهادة',
            status: 403
        );
    }


    public static function cancellationLimitReached(): self
    {
        return new self(
            title: '! فشل تنفيذ العملية',
            message: 'لقد استنفدت عدد مرات إلغاء طلب التوثيق الأكاديمي المسموح بها',
            status: 403
        );
    }

    public static function cancellableRequestNotFound(): self
    {
        return new self(
            title: '! فشل إلغاء الطلب',
            message: 'لا يوجد طلب توثيق أكاديمي قابل للإلغاء',
            status: 404
        );
    }


}
