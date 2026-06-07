<?php

namespace App\Exceptions\Api;

class LibraryMaterialException extends ApiException
{

    public static function tooManyPendingPublicMaterials(string $title): self
    {
        return new self(
            title: $title,
            message: 'لا يمكنك نشر محتوى عام جديد قبل مراجعة المحتويات العامة السابقة',
            status: 409
        );
    }

    public static function publicMaterialNotFound(): self
    {
        return new self(
            title: '! المحتوى غير متاح',
            message: 'المحتوى غير موجود أو غير متاح للعرض',
            status: 404
        );
    }

    public static function ownedMaterialNotFound(): self
    {
        return new self(
            title: '! المحتوى غير متاح',
            message: 'المحتوى غير موجود أو لا تملك صلاحية عرضه',
            status: 404
        );
    }

    public static function materialNotAvailableForInteraction(): self
    {
        return new self(
            title: '! لا يمكن تنفيذ العملية',
            message: 'المحتوى غير متاح للتفاعل',
            status: 404
        );
    }

    public static function notAvailable(): self
    {
        return new self(
            title: '! لا يمكن عرض المحتوى',
            message: 'هذا المحتوى غير متاح للعرض حالياً',
            status: 403
        );
    }

    public static function materialNotAvailableForReport(): self
    {
        return new self(
            title: '! لا يمكن إرسال البلاغ',
            message: 'المحتوى غير متاح للإبلاغ.',
            status: 404
        );
    }

    public static function alreadyReportedSameReasonForCurrentVersion(): self
    {
        return new self(
            title: '! لا يمكن إرسال البلاغ',
            message: 'لا يمكنك الإبلاغ مرة أخرى عن هذا المحتوى لنفس السبب على نفس النسخة الحالية.',
            status: 409
        );
    }

    public static function materialNotAvailableForDownload(): self
    {
        return new self(
            title: '! لا يمكن تنزيل المحتوى',
            message: 'المحتوى غير متاح للتنزيل.',
            status: 404
        );
    }

    public static function materialFileNotFound(): self
    {
        return new self(
            title: '! فشل تنزيل المحتوى',
            message: 'ملف المحتوى غير موجود.',
            status: 404
        );
    }

    public static function materialNotFound(): self
    {
        return new self(
            title: '! فشل مشاركة المحتوى',
            message: 'المحتوى غير موجود أو غير متاح للمشاركة',
            status: 404
        );
    }

    public static function unauthorizedAction(): self
    {
        return new self(
            title: '! لايمكن حذف المحتوى',
            message: 'المحتوى غير موجود او لاتمتلك صلاحية حذفه',
            status: 404,
        );
    }

    public static function reportedMaterialCannotBeUpdated(): self
    {
        return new self(
            title: '! لا يمكن تعديل المحتوى',
            message: 'لا يمكن تعديل المحتوى لأنه مبلغ عنه وينتظر المراجعة.',
            status: 409
        );
    }

    public static function cannotConvertPublicMaterialToPrivate(): self
    {
        return new self(
            title: '! لا يمكن تعديل نوع المحتوى',
            message: 'لا يمكن تحويل المحتوى العام إلى محتوى خاص.',
            status: 409
        );
    }

}
