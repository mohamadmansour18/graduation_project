<?php

namespace App\Exceptions\Api;

class TestException extends ApiException
{
    public static function notFound(): self
    {
        return new self(
            title: '! الاختبار غير موجود',
            message: 'لم يتم العثور على الاختبار المطلوب',
            status: 404
        );
    }

    public static function previewIsForOtherUsersOnly(): self
    {
        return new self(
            title: '! لا يمكن عرض عينة الاختبار',
            message: 'عينة الاختبار مخصصة للمستخدمين الآخرين وليست لصاحب الاختبار',
            status: 403
        );
    }

    public static function cannotLikeOwnTest(): self
    {
        return new self(
            title: '! لا يمكن تسجيل الإعجاب',
            message: 'لا يمكنك تسجيل الإعجاب على اختبار قمت بإنشائه',
            status: 403
        );
    }


    public static function cannotUnlikeOwnTest(): self
    {
        return new self(
            title: '! لا يمكن إزالة الإعجاب',
            message: 'لا يمكنك تنفيذ هذا الإجراء على اختبار قمت بإنشائه',
            status: 403
        );
    }

    public static function cannotBookmarkOwnTest(): self
    {
        return new self(
            title: '! لا يمكن حفظ الاختبار',
            message: 'لا يمكنك تسجيل الإعجاب على اختبار قمت بإنشائه',
            status: 403
        );
    }

    public static function cannotUnbookmarkOwnTest(): self
    {
        return new self(
            title: '! لا يمكن إزالة الحفظ',
            message: 'لا يمكنك تنفيذ هذا الإجراء على اختبار قمت بإنشائه',
            status: 403
        );
    }

}
