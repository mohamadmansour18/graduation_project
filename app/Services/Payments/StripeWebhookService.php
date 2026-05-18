<?php

namespace App\Services\Payments;

use App\Repositories\Payments\PaymentAttemptRepository;
use App\Repositories\Payments\TestPurchaseRepository;
use Illuminate\Support\Facades\Log;
use Stripe\Event;

class StripeWebhookService
{
    public function __construct(
        private readonly PaymentAttemptRepository $paymentAttemptRepository,
        private readonly TestPurchaseRepository $testPurchaseRepository,
    )
    {}

    public function handle(Event $event): void
    {
        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event),
            'checkout.session.expired' => $this->handleCheckoutSessionExpired($event),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event),

            default => $this->handleIgnoredEvent($event),
        };
    }

    private function handleCheckoutSessionCompleted(Event $event): void
    {
        $session = $event->data->object;

        if (($session->payment_status ?? null) !== 'paid') {
            Log::channel('daily')->info('Stripe checkout session completed but payment is not paid', [
                'stripe_event_id' => $event->id,
                'checkout_session_id' => $session->id ?? null,
                'payment_status' => $session->payment_status ?? null,
            ]);

            return;
        }

        $attemptId = $this->extractIntFromMetadata($session->metadata ?? null, 'payment_attempt_id');

        $attempt = $this->paymentAttemptRepository->findForCheckoutSession(
            attemptId: $attemptId,
            checkoutSessionId: $session->id,
        );

        if (! $attempt) {
            Log::channel('errors')->warning('Stripe completed checkout session has no matching payment attempt', [
                'stripe_event_id' => $event->id,
                'checkout_session_id' => $session->id,
                'metadata_attempt_id' => $attemptId,
            ]);

            return;
        }

        $paymentIntentId = is_string($session->payment_intent ?? null)
            ? $session->payment_intent
            : null;

        $this->paymentAttemptRepository->updateProviderReferencesIfMissing(
            attemptId: $attempt->id,
            checkoutSessionId: $session->id,
            paymentIntentId: $paymentIntentId,
        );

        $this->paymentAttemptRepository->markAsSucceeded($attempt->id);

        $attempt = $this->paymentAttemptRepository->findById($attempt->id);

        $purchase = $this->testPurchaseRepository->findById($attempt->test_purchase_id);

        if (! $purchase) {
            Log::channel('errors')->warning('Payment attempt has no matching test purchase', [
                'stripe_event_id' => $event->id,
                'payment_attempt_id' => $attempt->id,
                'test_purchase_id' => $attempt->test_purchase_id,
            ]);

            return;
        }

        $updatedPurchase = $this->testPurchaseRepository->markAsPaidFromAttempt(
            purchase: $purchase,
            attempt: $attempt,
        );

        Log::channel('audit')->info('Test purchase paid successfully', [
            'action' => 'test_purchase_paid',
            'purchase_id' => $updatedPurchase->id,
            'payment_attempt_id' => $attempt->id,
            'test_id' => $updatedPurchase->test_id,
            'buyer_user_id' => $updatedPurchase->buyer_user_id,
            'seller_user_id' => $updatedPurchase->seller_user_id,
            'gross_amount' => $updatedPurchase->gross_amount,
            'platform_fee_amount' => $updatedPurchase->platform_fee_amount,
            'seller_net_amount' => $updatedPurchase->seller_net_amount,
            'currency' => $updatedPurchase->currency,
            'payment_provider' => $updatedPurchase->payment_provider,
            'payment_reference' => $updatedPurchase->payment_reference,
            'stripe_event_id' => $event->id,
            'checkout_session_id' => $session->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | مكان استدعاء Event الإشعارات لاحقًا
        |--------------------------------------------------------------------------
        |
        | هنا أصبح test_purchases مدفوعًا بالفعل، وبالتالي has_purchased
        | في API تفاصيل الاختبار سيصبح true.
        |
        | مثال لاحقًا:
        |
        | TestPurchasePaid::dispatch($updatedPurchase, $attempt);
        |
        | Listeners مقترحة:
        | - SendPurchaseSuccessNotificationToBuyer
        | - SendNewTestSaleNotificationToSeller
        | - UpdateFinancialStatsAfterTestPurchase
        |
        | اجعل هذه الـ Listeners queued حتى لا نبطئ webhook response.
        |
        | قواعد مشروعك تنصح باستخدام Events/Listeners للآثار الجانبية المنفصلة،
        | واستخدام Queues للأعمال غير الحرجة التي لا يجب أن تؤخر الاستجابة.
        |
        */
    }

    private function handleCheckoutSessionExpired(Event $event): void
    {
        $session = $event->data->object;

        $attemptId = $this->extractIntFromMetadata($session->metadata ?? null, 'payment_attempt_id');

        $attempt = $this->paymentAttemptRepository->findForCheckoutSession(
            attemptId: $attemptId,
            checkoutSessionId: $session->id,
        );

        if (! $attempt) {
            Log::channel('daily')->info('Stripe expired checkout session has no matching payment attempt', [
                'stripe_event_id' => $event->id,
                'checkout_session_id' => $session->id ?? null,
                'metadata_attempt_id' => $attemptId,
            ]);

            return;
        }

        $this->paymentAttemptRepository->markAsExpired($attempt->id);

        $hasActiveAttempt = $this->paymentAttemptRepository
            ->hasActivePendingAttemptForPurchase($attempt->test_purchase_id);

        $this->testPurchaseRepository->markAsCancelledIfNoActiveAttempts(
            purchaseId: $attempt->test_purchase_id,
            hasActiveAttempt: $hasActiveAttempt,
        );

        Log::channel('daily')->info('Stripe checkout session expired', [
            'stripe_event_id' => $event->id,
            'checkout_session_id' => $session->id,
            'payment_attempt_id' => $attempt->id,
            'test_purchase_id' => $attempt->test_purchase_id,
            'has_active_attempt' => $hasActiveAttempt,
        ]);
    }

    private function handlePaymentIntentFailed(Event $event): void
    {
        $paymentIntent = $event->data->object;

        $attemptId = $this->extractIntFromMetadata(
            $paymentIntent->metadata ?? null,
            'payment_attempt_id'
        );

        $attempt = $this->paymentAttemptRepository->findForPaymentIntent(
            attemptId: $attemptId,
            paymentIntentId: $paymentIntent->id,
        );

        if (! $attempt) {
            Log::channel('daily')->info('Stripe failed payment intent has no matching payment attempt', [
                'stripe_event_id' => $event->id,
                'payment_intent_id' => $paymentIntent->id ?? null,
                'metadata_attempt_id' => $attemptId,
                'failure_code' => $paymentIntent->last_payment_error->code ?? null,
            ]);

            return;
        }

        $failureCode = $paymentIntent->last_payment_error->code ?? null;
        $failureMessage = $paymentIntent->last_payment_error->message ?? null;

        $this->paymentAttemptRepository->updateProviderReferencesIfMissing(
            attemptId: $attempt->id,
            checkoutSessionId: null,
            paymentIntentId: $paymentIntent->id,
        );

        $this->paymentAttemptRepository->recordFailureWithoutClosingAttempt(
            attemptId: $attempt->id,
            failureCode: $failureCode,
            failureMessage: $failureMessage,
        );

        Log::channel('daily')->info('Stripe payment intent failed', [
            'stripe_event_id' => $event->id,
            'payment_attempt_id' => $attempt->id,
            'test_purchase_id' => $attempt->test_purchase_id,
            'payment_intent_id' => $paymentIntent->id,
            'failure_code' => $failureCode,
        ]);

        /*
        |--------------------------------------------------------------------------
        | ملاحظة مهمة
        |--------------------------------------------------------------------------
        |
        | لا نحول test_purchases إلى ملغاة هنا.
        | فشل بطاقة واحدة لا يعني أن جلسة Checkout انتهت.
        | يمكن للمستخدم تجربة بطاقة أخرى.
        |
        | الإلغاء النهائي يحدث عند checkout.session.expired
        | أو إذا بنينا لاحقًا إلغاء صريح من المستخدم.
        |
        */
    }

    private function handleIgnoredEvent(Event $event): void
    {
        Log::channel('daily')->info('Stripe webhook event ignored', [
            'stripe_event_id' => $event->id,
            'event_type' => $event->type,
        ]);
    }

    private function extractIntFromMetadata(mixed $metadata, string $key): ?int
    {
        if (! $metadata || ! isset($metadata[$key])) {
            return null;
        }

        $value = $metadata[$key];

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }
}
