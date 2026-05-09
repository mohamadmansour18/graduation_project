<?php

namespace App\Exceptions\Api;

class FollowException extends ApiException
{
    public static function userNotFound(): self
    {
        return new self(
            title: '! المستخدم غير موجود',
            message: 'لم يتم العثور على المستخدم المطلوب',
            status: 404
        );
    }

    public static function cannotFollowYourself(): self
    {
        return new self(
            title: '! لا يمكن تنفيذ المتابعة',
            message: 'لا يمكنك متابعة نفسك',
            status: 403
        );
    }

    public static function cannotUnfollowYourself(): self
    {
        return new self(
            title: '! لا يمكن إلغاء المتابعة',
            message: 'لا يمكنك تنفيذ هذا الإجراء على نفسك',
            status: 403
        );
    }

    public static function alreadyFollowing(): self
    {
        return new self(
            title: '! المتابعة موجودة مسبقاً',
            message: 'أنت تتابع هذا المستخدم بالفعل',
            status: 409
        );
    }

    public static function notFollowing(): self
    {
        return new self(
            title: '! لا توجد متابعة',
            message: 'لا يمكنك إلغاء متابعة مستخدم لا تتابعه',
            status: 409
        );
    }
}
