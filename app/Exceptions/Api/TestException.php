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
}
