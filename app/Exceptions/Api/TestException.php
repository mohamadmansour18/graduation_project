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

}
