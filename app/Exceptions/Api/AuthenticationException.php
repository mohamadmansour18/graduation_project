<?php

namespace App\Exceptions\Api;

class AuthenticationException extends ApiException
{
    //--------------------------------[REGISTER]--------------------------------//
    public static function emailAlreadyRegistered(): self
    {
        return new self(
            title: '! فشل إنشاء الحساب',
            message: 'البريد الإلكتروني مستخدم بالفعل يرجى استخدام بريد إلكتروني آخر',
            status: 422
        );
    }

    //--------------------------------[LOGIN]--------------------------------//

    public static function invalidCredentials(): self
    {
        return new self(
            title: '! فشل تسجيل الدخول',
            message: 'البريد الإلكتروني أو كلمة المرور غير صحيحين',
            status: 401
        );
    }


    public static function emailNotVerified(): self
    {
        return new self(
            title: '! البريد الإلكتروني غير مؤكد',
            message: 'لم يتم تأكيد بريدك الإلكتروني بعد. يرجى إكمال خطوة التحقق من البريد أولًا',
            status: 403
        );
    }

    public static function onboardingIncomplete(?int $lastCompletedStep): self
    {
        return new self(
            title: 'حسابك غير مكتمل',
            message: 'لم تُكمل جميع خطوات إدخال المعلومات. يرجى المتابعة من الواجهة المطلوبة',
            status: 403,
            extraContext: [
                'last_completed_step' => $lastCompletedStep ?? 0 ,
            ]
        );
    }

    public static function accountBanned(string $reason, string $startsAt, ?string $endsAt, bool $isPermanent): self
    {
        return new self(
            title: '! الحساب محظور',
            message: $isPermanent ? 'حسابك محظور بشكل دائم' : 'حسابك محظور مؤقتًا',
            status: 403,
            extraContext: [
                'reason' => $reason,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'is_permanent' => $isPermanent,
            ]
        );
    }

    //--------------------------------[VERIFY]--------------------------------//

    public static function emailAlreadyVerified(): self
    {
        return new self(
            title: 'البريد الإلكتروني مؤكد مسبقًا',
            message: 'تم تأكيد البريد الإلكتروني مسبقًا.',
            status: 422
        );
    }

    public static function invalidEmailVerificationOtp(): self
    {
        return new self(
            title: 'رمز التحقق غير صالح',
            message: 'رمز التحقق غير صحيح أو غير صالح.',
            status: 422
        );
    }

    public static function expiredEmailVerificationOtp(): self
    {
        return new self(
            title: 'انتهت صلاحية الرمز',
            message: 'انتهت صلاحية رمز التحقق، يرجى طلب رمز جديد.',
            status: 422
        );
    }
}
