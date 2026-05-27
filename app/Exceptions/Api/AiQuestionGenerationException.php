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

    public static function providerApiKeyMissing(string $provider = 'AI Provider'): self
    {
        return new self(
            title: '! إعدادات الذكاء الاصطناعي غير مكتملة',
            message: 'خدمة توليد الأسئلة غير مهيأة حالياً، يرجى المحاولة لاحقاً',
            status: 500,
            extraContext: [
                'failure_code' => 'PROVIDER_API_KEY_MISSING',
                'provider' => $provider,
            ]
        );
    }

    public static function providerRequestFailed(string $provider, string $operation, int $status, ?string $responseBody = null): self
    {
        $failureCode = match (true) {
            $status === 429 => 'AI_PROVIDER_RATE_LIMITED',
            in_array($status, [408, 425, 500, 502, 503, 504], true) => 'AI_PROVIDER_TEMPORARILY_UNAVAILABLE',
            in_array($status, [401, 403], true) => 'AI_PROVIDER_AUTH_FAILED',
            in_array($status, [400, 413, 415, 422], true) => 'AI_PROVIDER_REQUEST_REJECTED',
            default => 'AI_PROVIDER_REQUEST_FAILED',
        };

        $message = match ($failureCode) {
            'AI_PROVIDER_RATE_LIMITED'
            => 'خدمة الذكاء الاصطناعي مشغولة حالياً أو وصلت إلى حدود الاستخدام، يرجى المحاولة بعد قليل',

            'AI_PROVIDER_TEMPORARILY_UNAVAILABLE'
            => 'خدمة الذكاء الاصطناعي غير متاحة مؤقتاً، يرجى المحاولة لاحقاً',

            'AI_PROVIDER_AUTH_FAILED'
            => 'خدمة توليد الأسئلة غير مهيأة حالياً، يرجى المحاولة لاحقاً',

            'AI_PROVIDER_REQUEST_REJECTED'
            => 'تعذر إرسال الطلب إلى خدمة الذكاء الاصطناعي بسبب مشكلة في الملفات أو صيغة الطلب',

            default
            => 'فشل الاتصال بخدمة الذكاء الاصطناعي، يرجى المحاولة لاحقاً',
        };

        return new self(
            title: '! حدث خطأ أثناء توليد الأسئلة',
            message: $message,
            status: $status === 422 ? 422 : 500,
            extraContext: [
                'failure_code' => $failureCode,
                'provider' => $provider,
                'operation' => $operation,
                'provider_http_status' => $status,
                'provider_response_body' => self::limitContextText($responseBody),
            ]
        );
    }

    public static function providerConnectionFailed(string $provider, string $operation, ?string $reason = null): self
    {
        return new self(
            title: '! تعذر الاتصال بخدمة الذكاء الاصطناعي',
            message: 'تعذر الاتصال بخدمة الذكاء الاصطناعي حالياً، يرجى المحاولة لاحقاً',
            status: 500,
            extraContext: [
                'failure_code' => 'AI_PROVIDER_CONNECTION_FAILED',
                'provider' => $provider,
                'operation' => $operation,
                'reason' => self::limitContextText($reason),
            ]
        );
    }

    public static function providerInvalidResponse(string $provider, string $operation, ?string $reason = null): self
    {
        return new self(
            title: '! استجابة غير صالحة من خدمة الذكاء الاصطناعي',
            message: 'فشلت معالجة نتيجة الذكاء الاصطناعي، يرجى المحاولة لاحقاً',
            status: 500,
            extraContext: [
                'failure_code' => 'AI_PROVIDER_INVALID_RESPONSE',
                'provider' => $provider,
                'operation' => $operation,
                'reason' => self::limitContextText($reason),
            ]
        );
    }

    public static function temporaryFileReadFailed(?string $path = null): self
    {
        return new self(
            title: '! فشلت قراءة الملفات المرفقة',
            message: 'فشلت قراءة المستندات المرفقة، يرجى المحاولة لاحقاً',
            status: 500,
            extraContext: [
                'failure_code' => 'TEMPORARY_FILE_READ_FAILED',
                'storage_path' => $path,
            ]
        );
    }


    public static function temporaryFileMissing(?string $path = null): self
    {
        return new self(
            title: '! الملف المرفق غير موجود',
            message: 'تعذر العثور على أحد الملفات المرفقة، يرجى إعادة رفع الملفات والمحاولة مجدداً',
            status: 500,
            extraContext: [
                'failure_code' => 'TEMPORARY_FILE_MISSING',
                'storage_path' => $path,
            ]
        );
    }

    public static function providerUploadUrlMissing(string $provider): self
    {
        return new self(
            title: '! استجابة غير مكتملة من خدمة الذكاء الاصطناعي',
            message: 'فشل تجهيز الملفات قبل توليد الأسئلة، يرجى المحاولة لاحقاً',
            status: 500,
            extraContext: [
                'failure_code' => 'AI_PROVIDER_UPLOAD_URL_MISSING',
                'provider' => $provider,
            ]
        );
    }

    public static function providerUploadedFileResponseInvalid(string $provider): self
    {
        return new self(
            title: '! استجابة غير صالحة من خدمة الذكاء الاصطناعي',
            message: 'فشل تأكيد رفع الملفات إلى خدمة الذكاء الاصطناعي، يرجى المحاولة لاحقاً',
            status: 500,
            extraContext: [
                'failure_code' => 'AI_PROVIDER_UPLOADED_FILE_RESPONSE_INVALID',
                'provider' => $provider,
            ]
        );
    }

    public static function notEnoughEducationalContent(int $minimumRequired, int $generatedCount): self
    {
        return new self(
            title: '! المحتوى غير كافٍ',
            message: "المحتوى المرفوع لا يحتوي على معلومات كافية لتوليد أسئلة جيدة. تم توليد {$generatedCount} سؤال/أسئلة فقط، والحد الأدنى المقبول هو {$minimumRequired}",
            status: 422,
            extraContext: [
                'failure_code' => 'PROVIDER_NOT_ENOUGH_EDUCATIONAL_CONTENT',
                'minimum_required_questions' => $minimumRequired,
                'generated_questions_count' => $generatedCount,
            ]
        );
    }

    public static function contentIsNotEducational(): self
    {
        return new self(
            title: '! لا يمكن توليد الأسئلة',
            message: 'المحتوى المرفوع لا يبدو محتوى علمياً أو تعليمياً مناسباً لتوليد أسئلة منه',
            status: 422,
            extraContext: [
                'failure_code' => 'CONTENT_NOT_EDUCATIONAL',
            ]
        );
    }

    public static function invalidGeneratedQuestions(?string $reason = null): self
    {
        return new self(
            title: '! فشلت معالجة الأسئلة',
            message: 'لم تتمكن خدمة الذكاء الاصطناعي من توليد أسئلة صالحة، يرجى المحاولة لاحقاً',
            status: 500,
            extraContext: [
                'failure_code' => 'INVALID_GENERATED_QUESTIONS',
                'reason' => self::limitContextText($reason),
            ]
        );
    }

    public static function allProvidersTemporarilyUnavailable(): self
    {
        return new self(
            title: '! خدمات الذكاء الاصطناعي مشغولة حالياً',
            message: 'خدمات توليد الأسئلة مشغولة حالياً، يرجى المحاولة بعد قليل',
            status: 503,
            extraContext: [
                'failure_code' => 'ALL_AI_PROVIDERS_TEMPORARILY_UNAVAILABLE',
            ]
        );
    }

    public static function providerUnsupportedSourceType(string $provider, string $sourceType): self
    {
        return new self(
            title: '! نوع الملف غير مدعوم من هذا المزود',
            message: 'هذا النوع من الملفات غير مدعوم حالياً من أحد مزودي الذكاء الاصطناعي، سيتم تجربة مزود آخر إن وجد',
            status: 422,
            extraContext: [
                'failure_code' => 'AI_PROVIDER_UNSUPPORTED_SOURCE_TYPE',
                'provider' => $provider,
                'source_type' => $sourceType,
            ]
        );
    }

    private static function limitContextText(?string $text, int $limit = 1000): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = trim($text);

        if ($text === '') {
            return null;
        }

        return mb_strlen($text) > $limit
            ? mb_substr($text, 0, $limit) . '...'
            : $text;
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
