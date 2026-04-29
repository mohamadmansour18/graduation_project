<?php

namespace App\Exceptions\Api;

class InterestException extends ApiException
{
    public static function interestNotFound(): self
    {
        return new self(
            title: '! فشل جلب الاختبارات',
            message: 'التصنيف العلمي المطلوب غير موجود',
            status: 404
        );
    }
}
