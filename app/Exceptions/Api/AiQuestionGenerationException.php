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
            message: 'مفتاح Gemini API غير مضبوط داخل إعدادات النظام',
            status: 500
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
}
