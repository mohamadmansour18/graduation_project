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

    public static function notAvailable(): self
    {
        return new self(
            title: '! لا يمكن عرض الاختبار',
            message: 'هذا الاختبار غير متاح للعرض حالياً',
            status: 403
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

    public static function purchaseRequiredForDownload(): self
    {
        return new self(
            title: '! لا يمكن تنزيل الاختبار',
            message: 'يجب شراء الاختبار قبل إمكانية تنزيله',
            status: 403
        );
    }

    public static function downloadFileTooLarge(): self
    {
        return new self(
            title: '! لا يمكن تنزيل الاختبار',
            message: 'حجم الاختبار غير مناسب للتنزيل حالياً',
            status: 422
        );
    }

    public static function testNotAvailableForReview(): self
    {
        return new self(
            title: '! لا يمكن تقييم الاختبار',
            message: 'هذا الاختبار غير متاح للتقييم حالياً',
            status: 403
        );
    }

    public static function cannotReviewOwnTest(): self
    {
        return new self(
            title: '! لا يمكن تقييم الاختبار',
            message: 'لا يمكنك تقييم اختبار قمت بإنشائه',
            status: 403
        );
    }

    public static function purchaseRequiredForReview(): self
    {
        return new self(
            title: '! لا يمكن تقييم الاختبار',
            message: 'يجب شراء الاختبار قبل إمكانية تقييمه',
            status: 403
        );
    }

    public static function alreadyReviewed(): self
    {
        return new self(
            title: '! لا يمكن إضافة التقييم',
            message: 'لقد قمت بتقييم هذا الاختبار مسبقاً',
            status: 409
        );
    }

    public static function reviewNotFound(): self
    {
        return new self(
            title: '! التقييم غير موجود',
            message: 'لم يتم العثور على تقييم خاص بك لهذا الاختبار',
            status: 404
        );
    }

    public static function nothingToUpdate(): self
    {
        return new self(
            title: '! لم يتم تعديل التقييم',
            message: 'لم يتم إرسال أي تغيير جديد على التقييم',
            status: 422
        );
    }

    public static function reviewNotAvailable(): self
    {
        return new self(
            title: '! لا يمكن تنفيذ العملية',
            message: 'التقييم غير متاح للتصويت حالياً',
            status: 404
        );
    }

    public static function cannotVoteOnOwnReview(): self
    {
        return new self(
            title: '! لا يمكن تنفيذ العملية',
            message: 'لا يمكنك التصويت على تقييمك الخاص',
            status: 403
        );
    }

    public static function alreadyVoted(): self
    {
        return new self(
            title: '! لا يمكن تكرار التصويت',
            message: 'لقد قمت بإبداء رأيك على هذا التقييم مسبقاً',
            status: 409
        );
    }

    public static function feedbackNotFound(): self
    {
        return new self(
            title: '! لا يوجد رأي سابق',
            message: 'لا يوجد رأي سابق لك على هذا التقييم لإزالته',
            status: 404
        );
    }

    public static function testNotAvailableForReport(): self
    {
        return new self(
            title: '! لا يمكن إرسال البلاغ',
            message: 'هذا الاختبار غير متاح للإبلاغ حالياً',
            status: 403
        );
    }

    public static function privateTestCannotBeReported(): self
    {
        return new self(
            title: '! لا يمكن إرسال البلاغ',
            message: 'لا يمكن الإبلاغ عن اختبار خاص',
            status: 403
        );
    }

    public static function cannotReportOwnTest(): self
    {
        return new self(
            title: '! لا يمكن إرسال البلاغ',
            message: 'لا يمكنك الإبلاغ عن اختبار قمت بإنشائه',
            status: 403
        );
    }

    public static function purchaseRequiredForReport(): self
    {
        return new self(
            title: '! لا يمكن إرسال البلاغ',
            message: 'يجب شراء الاختبار قبل إمكانية الإبلاغ عنه',
            status: 403
        );
    }

    public static function alreadyReportedForSameReasonAndVersion(): self
    {
        return new self(
            title: '! لا يمكن إرسال البلاغ',
            message: 'لقد قمت بالإبلاغ عن هذا الاختبار لنفس السبب ضمن نفس النسخة مسبقاً',
            status: 409
        );
    }

    public static function testVersionChanged(): self
    {
        return new self(
            title: '! لا يمكن إرسال البلاغ',
            message: 'تم تحديث نسخة الاختبار أثناء تنفيذ الطلب، يرجى إعادة المحاولة',
            status: 409
        );
    }

    public static function cannotReportOwnReview(): self
    {
        return new self(
            title: '! لا يمكن إرسال البلاغ',
            message: 'لا يمكنك الإبلاغ عن تقييم قمت بإنشائه',
            status: 403
        );
    }

    public static function notAvailableToShare(): self
    {
        return new self(
            title: '! لا يمكن مشاركة الاختبار',
            message: 'هذا الاختبار غير متاح للمشاركة حالياً بسبب حالته',
            status: 403
        );
    }
}
