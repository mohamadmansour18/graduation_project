<?php

namespace App\Exceptions\Api;

class PasswordResetException extends ApiException
{
    public static function invalidResetOtp(): self
    {
        return new self(
            title: 'رمز التحقق غير صالح',
            message: 'رمز التحقق غير صحيح أو غير صالح',
            status: 422
        );
    }

    public static function expiredResetOtp(): self
    {
        return new self(
            title: 'انتهت صلاحية الرمز',
            message: 'انتهت صلاحية رمز التحقق، يرجى طلب رمز جديد.',
            status: 422
        );
    }

    public static function userNotFound(): self
    {
        return new self(
            title: 'فشل التحقق',
            message: 'المستخدم غير موجود',
            status: 404
        );
    }

    public static function invalidNewPassword(): self
    {
        return new self(
            title: 'فشل تحديث كلمة المرور',
            message: 'يرجى اختيار كلمة مرور مختلفة عن الكلمة الحالية',
            status: 404
        );
    }

    public static function emailNotVerified(): self
    {
        return new self(
            title: '! البريد الإلكتروني غير مؤكد',
            message: 'لا يمكن متابعة إدخال معلوماتك قبل تأكيد البريد الإلكتروني',
            status: 403
        );
    }
}
