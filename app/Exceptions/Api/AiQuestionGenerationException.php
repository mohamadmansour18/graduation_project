<?php

namespace App\Exceptions\Api;

class AiQuestionGenerationException extends ApiException
{
    public static function dailyLimitExceeded(int $limit): self
    {
        return new self(
            title: '! لا يمكن توليد الأسئلة حالياً',
            message: "لقد وصلت إلى الحد اليومي المسموح لتوليد الأسئلة وهو {$limit} طلب/طلبات في اليوم",
            status: 429
        );
    }

    public static function invalidImagesCount(int $min, int $max): self
    {
        return new self(
            title: '! الملفات المرفوعة غير صحيحة',
            message: "يجب رفع عدد صور بين {$min} و {$max}",
            status: 422
        );
    }

    public static function invalidPdfCount(): self
    {
        return new self(
            title: '! الملفات المرفوعة غير صحيحة',
            message: 'يجب رفع ملف PDF واحد فقط',
            status: 422
        );
    }

    public static function sourceTypeDoesNotMatchFiles(): self
    {
        return new self(
            title: '! الملفات المرفوعة غير متوافقة',
            message: 'نوع الملفات المرفوعة لا يتوافق مع نوع مصدر التوليد المحدد',
            status: 422
        );
    }

    public static function generationRequestNotFound(): self
    {
        return new self(
            title: '! طلب التوليد غير موجود',
            message: 'طلب توليد الأسئلة غير موجود أو لا تملك صلاحية الوصول إليه',
            status: 404
        );
    }

    public static function failedToStoreFiles(): self
    {
        return new self(
            title: '! فشل حفظ الملفات',
            message: 'حدث خطأ أثناء حفظ الملفات المؤقتة، يرجى المحاولة مرة أخرى',
            status: 500
        );
    }

    public static function providerApiKeyMissing(): self
    {
        return new self(
            title: '! إعدادات الذكاء الاصطناعي غير مكتملة',
            message: 'خدمة توليد الأسئلة غير مهيأة حاليا يرجى المحاولة لاحقا',
            status: 500,
            extraContext: [
                'failure_code' => 'PROVIDER_API_KEY_MISSING',
            ]
        );
    }

    public static function connectionFailed(int $status): self
    {
        $failureCode = $status === 429
            ? 'AI_PROVIDER_RATE_LIMITED'
            : 'AI_PROVIDER_REQUEST_FAILED';

        $failureMessage = $status === 429
            ? 'خدمة الذكاء الاصطناعي مشغولة حالياً، يرجى المحاولة بعد قليل'
            : 'فشل الاتصال بخدمة الذكاء الاصطناعي لرفع طلب ارفاق المستندات';

        return new self(
            title: '! حدث خطأ غير متوقع',
            message: $failureMessage,
            status: 500,
            extraContext: [
                'failure_code' => $failureCode,
            ]
        );
    }

    public static function TemporaryFileReadFailed(): self
    {
        return new self(
            title: '! إعدادات الذكاء الاصطناعي غير مكتملة',
            message: 'فشلت قراءة المستندات المرفقة يرجى المحاولة لاحقا',
            status: 500,
            extraContext: [
                'failure_code' => 'TEMPORARY_FILE_READ_FAILED',
            ]
        );
    }


    public static function providerRequestFailed(): self
    {
        return new self(
            title: '! فشل توليد الأسئلة',
            message: 'تعذر الاتصال بخدمة الذكاء الاصطناعي حالياً، يرجى المحاولة لاحقاً',
            status: 503
        );
    }

    public static function invalidGeneratedQuestions(): self
    {
        return new self(
            title: '! فشل توليد الأسئلة',
            message: 'تم توليد نتيجة غير صالحة من خدمة الذكاء الاصطناعي، يرجى المحاولة مرة أخرى',
            status: 422
        );
    }

    public static function notEnoughEducationalContent(int $minimumRequired, int $generatedCount): self
    {
        return new self(
            title: '! المحتوى غير كافٍ',
            message: "المحتوى المرفوع لا يحتوي على معلومات كافية لتوليد أسئلة جيدة. تم توليد {$generatedCount} سؤال/أسئلة فقط، والحد الأدنى المقبول هو {$minimumRequired}",
            status: 422
        );
    }

    public static function contentIsNotEducational(): self
    {
        return new self(
            title: '! لا يمكن توليد الأسئلة',
            message: 'المحتوى المرفوع لا يبدو محتوى علمياً أو تعليمياً مناسباً لتوليد أسئلة منه',
            status: 422
        );
    }

    public static function imageTooSmall(int $fileIndex, int $minWidth, int $minHeight): self
    {
        return new self(
            title: '! الصورة المرفوعة غير صالحة',
            message: "الصورة رقم {$fileIndex} صغيرة جداً. الحد الأدنى المقبول هو {$minWidth}x{$minHeight} بكسل",
            status: 422
        );
    }

    public static function imageTooLargeToProcess(int $fileIndex): self
    {
        return new self(
            title: '! الصورة المرفوعة غير صالحة',
            message: "الصورة رقم {$fileIndex} كبيرة جداً ولا يمكن فحصها بكفاءة",
            status: 422
        );
    }

    public static function imageIsBlankOrUniform(int $fileIndex): self
    {
        return new self(
            title: '! الصورة المرفوعة فارغة',
            message: "الصورة رقم {$fileIndex} تبدو فارغة أو بلون واحد تقريباً، يرجى رفع صورة تحتوي على محتوى واضح",
            status: 422
        );
    }

    public static function imageCannotBeProcessed(int $fileIndex): self
    {
        return new self(
            title: '! الصورة المرفوعة غير صالحة',
            message: "تعذر قراءة الصورة رقم {$fileIndex}، يرجى رفع صورة صالحة",
            status: 422
        );
    }

    public static function invalidPdfFile(): self
    {
        return new self(
            title: '! ملف PDF غير صالح',
            message: 'تعذر فتح ملف PDF أو قراءة بنيته، يرجى رفع ملف PDF صالح',
            status: 422
        );
    }

    public static function emptyPdfFile(): self
    {
        return new self(
            title: '! ملف PDF فارغ',
            message: 'ملف PDF لا يحتوي على صفحات صالحة',
            status: 422
        );
    }
}
