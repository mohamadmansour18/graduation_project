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

    public static function viewerNotFound(): self
    {
        return new self(
            title: '! المستخدم غير موجود',
            message: 'لم يتم العثور على بيانات المستخدم الحالي',
            status: 404
        );
    }

    public static function contentNotAvailable(): self
    {
        return new self(
            title: '! لا يمكن جلب محتوى الاختبار',
            message: 'محتوى هذا الاختبار غير متاح للعرض حاليا ولايمكنك التفاعل معهً',
            status: 403
        );
    }

    public static function purchaseRequiredForContent(): self
    {
        return new self(
            title: '! لا يمكن جلب محتوى الاختبار',
            message: 'يجب شراء الاختبار قبل إمكانية التفاعل مع محتواه',
            status: 403
        );
    }

    public static function statusHistoryNotAvailable(): self
    {
        return new self(
            title: '! لا يمكن جلب سجل الحالة',
            message: 'سجل الحالة غير متاح لهذا الاختبار أو أنك لا تملك صلاحية الوصول إليه',
            status: 403
        );
    }

    public static function revisionRequestsNotAvailable(): self
    {
        return new self(
            title: '! لا يمكن جلب التعديلات المطلوبة',
            message: 'التعديلات المطلوبة غير متاحة لهذا الاختبار أو أنك لا تملك صلاحية الوصول إليها',
            status: 403
        );
    }

    public static function tooManyPendingPublicTestsForVerifiedUser(): self
    {
        return new self(
            title: '! لا يمكن إنشاء الاختبار',
            message: 'لا يمكن إنشاء اختبار عام جديد لأن لديك ثلاثة اختبارات عامة قيد المراجعة أو بانتظار التعديل',
            status: 422
        );
    }

    public static function tooManyPendingPublicTestsForUnverifiedUser(): self
    {
        return new self(
            title: '! لا يمكن إنشاء الاختبار',
            message: 'لا يمكن إنشاء اختبار عام جديد لأن المستخدم غير موثق أكاديمياً ولديه اختبار عام قيد المراجعة أو بانتظار التعديل',
            status: 422
        );
    }

    public static function tooManyPrivateTestsToday(): self
    {
        return new self(
            title: '! لا يمكن إنشاء الاختبار',
            message: 'لا يمكن إنشاء أكثر من خمسة اختبارات خاصة في اليوم الواحد',
            status: 422
        );
    }

    public static function privateTestCannotHavePrice(): self
    {
        return new self(
            title: '! بيانات الاختبار غير صحيحة',
            message: 'لا يمكن إضافة سعر للاختبار الخاص',
            status: 422
        );
    }

    public static function privateTestCannotHavePreviewQuestions(): self
    {
        return new self(
            title: '! بيانات الاختبار غير صحيحة',
            message: 'لا يمكن اختيار أسئلة كعينة للاختبار الخاص',
            status: 422
        );
    }

    public static function publicTestMustHaveExactPreviewQuestionsCount(int $requiredCount): self
    {
        return new self(
            title: '! عينة الأسئلة غير صحيحة',
            message: "يجب اختيار {$requiredCount} سؤال/أسئلة كعينة ظاهرة للاختبار العام",
            status: 422
        );
    }

    public static function questionMustHaveExactlyOneCorrectOption(int $questionNumber): self
    {
        return new self(
            title: '! بيانات السؤال غير صحيحة',
            message: "السؤال رقم {$questionNumber} يجب أن يحتوي على إجابة صحيحة واحدة فقط",
            status: 422
        );
    }

    public static function notOwner(string $message): self
    {
        return new self(
            title: '! لا تملك الصلاحية',
            message: $message,
            status: 403
        );
    }

    public static function alreadyDeleted(): self
    {
        return new self(
            title: '! فشل حذف الاختبار',
            message: 'لا يمكن حذف اختبار محذوف مسبقاً',
            status: 409
        );
    }

    public static function testCannotBeEdited(): self
    {
        return new self(
            title: '! لا يمكن تعديل الاختبار',
            message: 'لا يمكن تعديل الاختبار في حالته الحالية',
            status: 409
        );
    }

    public static function cannotConvertPublicToPrivate(): self
    {
        return new self(
            title: '! لا يمكن تغيير نوع الاختبار',
            message: 'لا يمكن تحويل اختبار عام إلى اختبار خاص',
            status: 422
        );
    }

    public static function previewQuestionsRequired(): self
    {
        return new self(
            title: '! أسئلة المعاينة مطلوبة',
            message: 'عند تحويل الاختبار من خاص إلى عام يجب تحديد أسئلة المعاينة المطلوبة',
            status: 422
        );
    }

    public static function invalidPreviewQuestionsCount(int $requiredCount): self
    {
        return new self(
            title: '! عدد أسئلة المعاينة غير صحيح',
            message: "يجب تحديد {$requiredCount} سؤال معاينة بالضبط لهذا الاختبار",
            status: 422
        );
    }

    public static function incompleteRevisionRequests(): self
    {
        return new self(
            title: '! لم تكتمل التعديلات المطلوبة',
            message: 'يجب إكمال جميع التعديلات المطلوبة من مركز التحكم قبل إرسال الاختبار للمراجعة',
            status: 422
        );
    }

    public static function forbiddenScientificChangeInRevision(): self
    {
        return new self(
            title: '! تعديل غير مسموح',
            message: 'في حالة يحتاج تعديل، يمكنك تعديل المعلومات العلمية المطلوبة فقط من مركز التحكم',
            status: 422
        );
    }

    public static function invalidQuestionPayload(): self
    {
        return new self(
            title: '! بيانات الأسئلة غير صحيحة',
            message: 'بيانات الأسئلة المرسلة غير صحيحة أو لا تتبع بنية الاختبار',
            status: 422
        );
    }

    public static function testCannotBeAccessed(): self
    {
        return new self(
            title: '! لا يمكن التفاعل مع الاختبار',
            message: 'لا يمكن التفاعل مع هذا الاختبار في حالته الحالية',
            status: 403
        );
    }

    public static function noTestsMatchFilter(): self
    {
        return new self(
            title: '! لا توجد اختبارات',
            message: 'لا توجد اختبارات مطابقة للفلتر المطلوب',
            status: 404
        );
    }
}
