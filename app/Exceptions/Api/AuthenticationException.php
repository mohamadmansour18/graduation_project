<?php

namespace App\Exceptions\Api;

class AuthenticationException extends ApiException
{
    public static function emailAlreadyRegistered(): self
    {
        return new self(
            title: 'فشل إنشاء الحساب',
            message: 'البريد الإلكتروني مستخدم بالفعل يرجى استخدام بريد إلكتروني آخر',
            status: 422
        );
    }
}
