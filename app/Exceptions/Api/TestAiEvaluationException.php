<?php

namespace App\Exceptions\Api;

class TestAiEvaluationException extends ApiException
{
    public static function testNotFound(): self
    {
        return new self(
            title: '! الاختبار غير موجود',
            message: 'الاختبار غير موجود أو لا يمكن الوصول إليه من لوحة التحكم',
            status: 404
        );
    }

    public static function evaluationRequestNotFound(): self
    {
        return new self(
            title: '! طلب تقييم الاختبار غير موجود',
            message: 'طلب تقييم الاختبار غير موجود أو لا يمكن الوصول إليه',
            status: 404
        );
    }

    public static function testHasNoQuestions(): self
    {
        return new self(
            title: '! لا يمكن تقييم الاختبار',
            message: 'لا يحتوي الاختبار على أسئلة قابلة للتقييم',
            status: 422
        );
    }

    public static function testStatusDoesNotAllowAiEvaluation(): self
    {
        return new self(
            title: '! لا يمكن طلب تقييم الذكاء الاصطناعي',
            message: 'لا يمكن طلب تقييم الذكاء الاصطناعي لاختبار تمت الموافقة عليه أو يحتاج إلى تعديل',
            status: 422
        );
    }

    public static function providerApiKeyMissing(string $provider = 'AI Provider'): self
    {
        return new self(
            title: '! إعدادات الذكاء الاصطناعي غير مكتملة',
            message: 'خدمة تقييم الاختبار غير مهيأة حالياً، يرجى المحاولة لاحقاً',
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

        return new self(
            title: '! حدث خطأ أثناء تقييم الاختبار',
            message: 'فشل الاتصال بخدمة الذكاء الاصطناعي، يرجى المحاولة لاحقاً',
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
            message: 'فشلت معالجة نتيجة تقييم الاختبار، يرجى المحاولة لاحقاً',
            status: 500,
            extraContext: [
                'failure_code' => 'AI_PROVIDER_INVALID_RESPONSE',
                'provider' => $provider,
                'operation' => $operation,
                'reason' => self::limitContextText($reason),
            ]
        );
    }

    private static function limitContextText(?string $text, int $limit = 2000): ?string
    {
        if ($text === null) {
            return null;
        }

        return mb_substr($text, 0, $limit);
    }
}
