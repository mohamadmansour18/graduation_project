<?php

namespace App\Services\Payments;

use App\DTOs\Payments\CreateCheckoutSessionData;
use App\Enums\Payments\PaymentProvider;
use App\Enums\Payments\PaymentStatus;
use App\Enums\TestReviewStatus;
use App\Enums\TestType;
use App\Exceptions\Api\PaymentException;
use App\Repositories\Payments\PaymentAttemptRepository;
use App\Repositories\Payments\TestPaymentRepository;
use App\Repositories\Payments\TestPurchaseRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Throwable;

class PurchaseService
{
    public function __construct(
        private readonly TestPaymentRepository $testPaymentRepository,
        private readonly TestPurchaseRepository $testPurchaseRepository,
        private readonly PurchaseMoneyCalculator $moneyCalculator,
        private readonly PaymentManager $paymentManager,
        private readonly PaymentAttemptRepository $paymentAttemptRepository,
    )
    {}

    public function createCheckoutSessionForTest(int $testId, int $buyerUserId): array
    {
        /*
        |--------------------------------------------------------------------------
        | 1. جلب بيانات الاختبار المطلوبة للدفع
        |--------------------------------------------------------------------------
        |
        | لا نجلب كل بيانات الاختبار، فقط البيانات اللازمة للتحقق وإنشاء الدفع.
        |
        */
        $test = $this->testPaymentRepository->findTestForPurchase($testId);

        if (! $test) {
            throw PaymentException::testNotFound();
        }

        /*
        |--------------------------------------------------------------------------
        | 2. التحقق من قواعد شراء الاختبار
        |--------------------------------------------------------------------------
        |
        | لا نسمح بشراء اختبار خاص، أو غير معتمد، أو مجاني، أو مملوك للمستخدم نفسه.
        |
        */
        $this->ensureTestCanBePurchased($test, $buyerUserId);

        /*
        |--------------------------------------------------------------------------
        | 3. تحديد العملة وحساب توزيع المال
        |--------------------------------------------------------------------------
        |
        | grossAmount: السعر الكامل.
        | platformFeeAmount: ربح المنصة.
        | sellerNetAmount: صافي ربح صاحب الاختبار.
        |
        */
        $currency = (string) config('payments.default_currency', 'usd');

        $money = $this->moneyCalculator->calculate(
            grossAmount: (float) $test->price,
            currency: $currency,
        );

        /*
        |--------------------------------------------------------------------------
        | 4. تحديد مزود الدفع
        |--------------------------------------------------------------------------
        |
        | حاليًا provider = stripe.
        | لاحقًا يمكن إضافة google_play أو apple_iap أو demo.
        |
        */
        $providerValue = (string) config(
            'payments.default_provider',
            PaymentProvider::Stripe->value
        );

        $provider = PaymentProvider::tryFrom($providerValue);

        if (! $provider) {
            throw PaymentException::unsupportedPaymentProvider();
        }

        /*
        |--------------------------------------------------------------------------
        | 5. تجهيز سجل الشراء النهائي test_purchases
        |--------------------------------------------------------------------------
        |
        | هذا السجل يمثل حق الوصول النهائي للاختبار.
        | لا يمثل كل محاولة دفع.
        |
        | إذا كان السجل غير موجود: ننشئه.
        | إذا كان موجودًا وغير مدفوع: نعيد تهيئته.
        | إذا كان مدفوعًا: نمنع الشراء.
        |
        */
        $purchase = $this->testPurchaseRepository->preparePurchaseRecord([
            'test_id' => $test->id,
            'buyer_user_id' => $buyerUserId,
            'seller_user_id' => $test->creator_user_id,

            'gross_amount' => $money->grossAmount,
            'platform_fee_amount' => $money->platformFeeAmount,
            'seller_net_amount' => $money->sellerNetAmount,
            'currency' => $money->currency,

            'payment_provider' => $provider->value,
        ]);

        if ($purchase->payment_status === PaymentStatus::Paid->value) {
            throw PaymentException::testAlreadyPurchased();
        }

        /*
        |--------------------------------------------------------------------------
        | 6. تنظيف محلي سريع للمحاولات المنتهية لنفس الشراء
        |--------------------------------------------------------------------------
        |
        | قبل أن نبحث عن جلسة قابلة لإعادة الاستخدام، نحول أي attempt انتهى وقتها
        | من "معلقة" إلى "منتهية".
        |
        | هذا لا يغني عن webhook ولا عن command التنظيف، لكنه يحسن السلوك فورًا
        | عند ضغط المستخدم مرة أخرى على زر الشراء.
        |
        */
        $this->paymentAttemptRepository
            ->expireLocalPendingAttemptsForPurchase($purchase->id);

        /*
        |--------------------------------------------------------------------------
        | 7. إعادة استخدام جلسة Stripe صالحة إن وجدت
        |--------------------------------------------------------------------------
        |
        | إذا كان المستخدم لديه Checkout Session صالحة ولم تنتهِ بعد،
        | نرجع نفس checkout_url مباشرة بدون الاتصال بـ Stripe.
        |
        | هذا يحسن الأداء ويقلل احتمالات أخطاء الشبكة مثل:
        | - SSL connection timeout
        | - Could not resolve host: api.stripe.com
        |
        */
        $reusableAttempt = $this->paymentAttemptRepository
            ->findReusablePendingAttemptForPurchase($purchase->id);

        if ($reusableAttempt) {
            return [
                'purchase_id' => $purchase->id,
                'payment_attempt_id' => $reusableAttempt->id,
                'provider' => $reusableAttempt->payment_provider,
                'checkout_session_id' => $reusableAttempt->provider_reference,
                'checkout_url' => $reusableAttempt->checkout_url,
                'expires_at' => $reusableAttempt->expires_at
                    ? Carbon::parse($reusableAttempt->expires_at)->timestamp
                    : null,
                'reused_existing_session' => true,
                'amount' => [
                    'gross_amount' => $money->grossAmount,
                    'platform_fee_amount' => $money->platformFeeAmount,
                    'seller_net_amount' => $money->sellerNetAmount,
                    'currency' => $money->currency,
                ],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 8. إنشاء محاولة دفع جديدة payment_attempts
        |--------------------------------------------------------------------------
        |
        | هنا لا ننشئ حق وصول جديد، بل محاولة دفع جديدة مرتبطة بنفس test_purchase.
        |
        */
        $expiresAt = now()
            ->addMinutes((int) config('payments.checkout_session_expires_after_minutes', 30))
            ->timestamp;

        $attempt = $this->paymentAttemptRepository->createPendingAttempt([
            'test_purchase_id' => $purchase->id,
            'payment_provider' => $provider->value,
            'amount' => $money->grossAmount,
            'currency' => $money->currency,
            'expires_at' => Carbon::createFromTimestamp($expiresAt),
            'metadata' => [
                'source' => 'mobile_app',
                'purchase_type' => 'test',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | 9. إنشاء Stripe Checkout Session
        |--------------------------------------------------------------------------
        |
        | هذه هي الخطوة الوحيدة التي تتصل فعليًا بـ Stripe.
        |
        | إذا نجحت:
        | - نخزن checkout_session_id
        | - نخزن checkout_url
        | - نخزن payment_intent إن وجد
        |
        | إذا فشلت:
        | - نسجل المحاولة كفاشلة
        | - نلغي الشراء إذا لم توجد محاولات فعالة
        | - نرمي PaymentException واضح للفرونت
        |
        */
        try {
            $checkoutSession = $this->paymentManager
                ->driver($provider)
                ->createCheckoutSession(new CreateCheckoutSessionData(
                    purchaseId: $purchase->id,
                    attemptId: $attempt->id,
                    testId: $test->id,
                    buyerUserId: $buyerUserId,
                    sellerUserId: $test->creator_user_id,
                    testTitle: $test->title,
                    money: $money,
                    successUrl: (string) config('payments.success_url'),
                    cancelUrl: (string) config('payments.cancel_url'),
                    expiresAt: $expiresAt,
                    metadata: [
                        'source' => 'mobile_app',
                        'purchase_type' => 'test',
                    ],
                ));

            $this->paymentAttemptRepository->attachStripeCheckoutSession(
                attemptId: $attempt->id,
                checkoutSessionId: $checkoutSession->checkoutSessionId,
                checkoutUrl: $checkoutSession->checkoutUrl,
                paymentIntentId: $checkoutSession->paymentIntentId,
                expiresAt: $checkoutSession->expiresAt,
            );

            return [
                'purchase_id' => $purchase->id,
                'payment_attempt_id' => $attempt->id,
                'provider' => $checkoutSession->provider,
                'checkout_session_id' => $checkoutSession->checkoutSessionId,
                'checkout_url' => $checkoutSession->checkoutUrl,
                'expires_at' => $checkoutSession->expiresAt,
                'reused_existing_session' => false,
                'amount' => [
                    'gross_amount' => $money->grossAmount,
                    'platform_fee_amount' => $money->platformFeeAmount,
                    'seller_net_amount' => $money->sellerNetAmount,
                    'currency' => $money->currency,
                ],
            ];
        } catch (Throwable $exception) {
            /*
            |--------------------------------------------------------------------------
            | فشل إنشاء جلسة Stripe
            |--------------------------------------------------------------------------
            |
            | هذا لا يعني أن الدفع فشل من المستخدم، بل يعني أننا لم نستطع تجهيز
            | صفحة الدفع أصلًا بسبب خطأ اتصال أو خطأ من Stripe.
            |
            */
            $this->paymentAttemptRepository->markAsFailed(
                attemptId: $attempt->id,
                failureCode: 'checkout_session_creation_failed',
                failureMessage: $exception->getMessage(),
            );

            /*
            |--------------------------------------------------------------------------
            | إلغاء الشراء إذا لم تعد هناك أي محاولة فعالة
            |--------------------------------------------------------------------------
            |
            | حتى لا يبقى test_purchase بحالة معلقة للأبد بعد فشل إنشاء الجلسة.
            |
            */
            $hasActiveAttempt = $this->paymentAttemptRepository
                ->hasActivePendingAttemptForPurchase($purchase->id);

            $this->testPurchaseRepository->markAsCancelledIfNoActiveAttempts(
                purchaseId: $purchase->id,
                hasActiveAttempt: $hasActiveAttempt,
            );

            Log::channel('errors')->error('Stripe checkout session creation failed', [
                'purchase_id' => $purchase->id,
                'payment_attempt_id' => $attempt->id,
                'test_id' => $test->id,
                'buyer_user_id' => $buyerUserId,
                'provider' => $provider->value,
                'message' => $exception->getMessage(),
            ]);

            throw PaymentException::checkoutSessionCreationFailed();
        }
    }

    private function ensureTestCanBePurchased(object $test, int $buyerUserId): void
    {
        /*
        |--------------------------------------------------------------------------
        | لا يمكن للمستخدم شراء اختباره الخاص
        |--------------------------------------------------------------------------
        */
        if ((int) $test->creator_user_id === $buyerUserId) {
            throw PaymentException::cannotPurchaseOwnTest();
        }

        /*
        |--------------------------------------------------------------------------
        | يجب أن يكون الاختبار عامًا
        |--------------------------------------------------------------------------
        */
        if ($test->test_type !== config('payments.tests.public_type_value')) {
            throw PaymentException::testIsNotPublic();
        }

        /*
        |--------------------------------------------------------------------------
        | يجب أن يكون الاختبار معتمدًا
        |--------------------------------------------------------------------------
        */
        if ($test->review_status !== config('payments.tests.approved_review_status_value')) {
            throw PaymentException::testIsNotApproved();
        }

        /*
        |--------------------------------------------------------------------------
        | يجب أن يكون الاختبار مدفوعًا
        |--------------------------------------------------------------------------
        */
        if ($test->price === null || (float) $test->price <= 0) {
            throw PaymentException::testIsFree();
        }

        /*
        |--------------------------------------------------------------------------
        | منع الشراء إذا كان المستخدم اشترى الاختبار مسبقًا
        |--------------------------------------------------------------------------
        |
        | هذا فحص سريع قبل الدخول في تجهيز purchase/attempt.
        | ويوجد فحص آخر بعد preparePurchaseRecord لحماية أفضل.
        |
        */
        if ($this->testPurchaseRepository->userHasPaidPurchase($test->id, $buyerUserId)) {
            throw PaymentException::testAlreadyPurchased();
        }
    }
}
