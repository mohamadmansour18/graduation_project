<?php

namespace App\Exceptions\Api;

class PublicProfileException extends ApiException
{
    public static function cannotViewOwnPublicProfile(): self
    {
        return new self(
            title: '! لا يمكن تنفيذ العملية',
            message: 'هذا المسار مخصص لعرض ملفات المستخدمين الآخرين فقط',
            status: 403
        );
    }
    public static function profileShareLinkNotFound(): self
    {
        return new self(
            title: '! الرابط غير صالح',
            message: 'رابط الملف الشخصي غير موجود أو لم يعد صالحاً',
            status: 404
        );
    }

    public static function publicFolderNotFound(): self
    {
        return new self(
            title: '! القائمة غير موجودة',
            message: 'القائمة غير موجودة أو غير متاحة للعرض',
            status: 404
        );
    }

    public static function academicCertificateNotFound(): self
    {
        return new self(
            title: '! الشهادة غير متاحة',
            message: 'لا توجد شهادة جامعية موثقة لهذا المستخدم',
            status: 404
        );
    }

}
