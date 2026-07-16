<?php

namespace App\Exceptions\Api;

class PaymentException extends ApiException
{
    public static function testNotFound(): self
    {
        return new self(
            title: '! فشل إنشاء عملية الدفع',
            message: 'الاختبار المطلوب غير موجود',
            status: 404
        );
    }

    public static function testIsNotPublic(): self
    {
        return new self(
            title: '! لا يمكن شراء هذا الاختبار',
            message: 'لا يمكن شراء اختبار غير عام',
            status: 403
        );
    }

    public static function testIsNotApproved(): self
    {
        return new self(
            title: '! لا يمكن شراء هذا الاختبار',
            message: 'لا يمكن شراء اختبار غير معتمد أو غير منشور',
            status: 403
        );
    }

    public static function testIsFree(): self
    {
        return new self(
            title: '! لا يمكن شراء هذا الاختبار',
            message: 'هذا الاختبار مجاني ولا يحتاج إلى عملية شراء',
            status: 400
        );
    }

    public static function cannotPurchaseOwnTest(): self
    {
        return new self(
            title: '! لا يمكن شراء هذا الاختبار',
            message: 'لا يمكنك شراء اختبار قمت بإنشائه بنفسك',
            status: 403
        );
    }

    public static function testAlreadyPurchased(): self
    {
        return new self(
            title: '! الاختبار مشترى مسبقًا',
            message: 'لقد قمت بشراء هذا الاختبار مسبقًا ويمكنك الوصول إليه بالفعل',
            status: 409
        );
    }

    public static function invalidTestPrice(): self
    {
        return new self(
            title: '! سعر الاختبار غير صالح',
            message: 'لا يمكن إنشاء عملية دفع لاختبار لا يملك سعرًا صالحًا',
            status: 422
        );
    }

    public static function unsupportedPaymentProvider(): self
    {
        return new self(
            title: '! مزود الدفع غير مدعوم',
            message: 'مزود الدفع المطلوب غير مدعوم حاليًا',
            status: 422
        );
    }

    public static function checkoutSessionCreationFailed(): self
    {
        return new self(
            title: '! فشل إنشاء جلسة الدفع',
            message: 'حدث خطأ أثناء تجهيز صفحة الدفع، يرجى المحاولة لاحقًا',
            status: 502
        );
    }

    public static function currencyConversionUnavailable(): self
    {
        return new self(
            title: '! تعذر تحويل العملة',
            message: 'تعذر الحصول على سعر صرف صالح حاليًا، يرجى المحاولة لاحقًا',
            status: 503
        );
    }
}
