<?php

namespace App\Exceptions\Api;

class DashboardUserException extends ApiException
{
    public static function onlyOwnerCanCreateSupervisor(): self
    {
        return new self(
            title: '! غير مصرح',
            message: 'إضافة مشرف جديد متاحة للمالك فقط',
            status: 403
        );
    }

    public static function supervisorRoleNotFound(): self
    {
        return new self(
            title: '! فشل إنشاء المشرف',
            message: 'دور المشرف غير موجود في النظام',
            status: 500
        );
    }

    public static function academicVerificationAssetNotFound(): self
    {
        return new self(
            title: '! تعذر عرض الوثيقة',
            message: 'الوثيقة المطلوبة غير موجودة',
            status: 404
        );
    }

    public static function academicVerificationAssetFileNotFound(): self
    {
        return new self(
            title: '! تعذر عرض الوثيقة',
            message: 'ملف الوثيقة غير موجود في التخزين',
            status: 404
        );
    }

    public static function userAlreadyBanned(): self
    {
        return new self(
            title: '! لا يمكن تنفيذ العملية',
            message: 'هذا المستخدم لديه حظر قائم أو مجدول مسبقاً',
            status: 409
        );
    }

    public static function invalidTemporaryBanDuration(): self
    {
        return new self(
            title: '! مدة الحظر غير صالحة',
            message: 'مدة الحظر المؤقت يجب ألا تقل عن يوم واحد وألا تزيد عن 30 يوماً',
            status: 422
        );
    }

    public static function userNotFound(): self
    {
        return new self(
            title: '! غير موجود',
            message: 'المشرف او المالك المطلوب غير موجود',
            status: 404
        );
    }

    public static function supervisorNotFound(): self
    {
        return new self(
            title: '! غير موجود',
            message: 'المشرف او المالك المطلوب غير موجود',
            status: 404
        );
    }

    public static function invalidOldPassword(): self
    {
        return new self(
            title: '! فشل تعديل كلمة المرور',
            message: 'كلمة المرور القديمة غير صحيحة',
            status: 422
        );
    }

    public static function newPasswordMustBeDifferent(): self
    {
        return new self(
            title: '! فشل تعديل كلمة المرور',
            message: 'كلمة المرور الجديدة يجب أن تكون مختلفة عن كلمة المرور القديمة',
            status: 422
        );
    }
}
