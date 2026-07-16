<?php

namespace App\Services\Payments;

use App\DTOs\Notifications\NotificationPayload;
use App\Enums\PaymentStatus;
use App\Events\TestPurchasePaid;
use App\Helpers\BuildActor;
use App\Helpers\ImageProcessor;
use App\Repositories\Payments\PaymentAttemptRepository;
use App\Repositories\Payments\TestPurchaseRepository;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Support\Facades\Log;
use Stripe\Event;

class StripeWebhookService
{
    public function __construct(
        private readonly PaymentAttemptRepository $paymentAttemptRepository,
        private readonly TestPurchaseRepository $testPurchaseRepository,
        private readonly NotificationCenter $notificationCenter,
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

        if (! $this->sessionAmountMatchesAttempt($session, $attempt)) {
            $this->paymentAttemptRepository->markAsFailed(
                attemptId: $attempt->id,
                failureCode: 'stripe_amount_mismatch',
                failureMessage: 'Stripe checkout session amount or currency does not match the local payment attempt.',
            );

            $hasActiveAttempt = $this->paymentAttemptRepository
                ->hasActivePendingAttemptForPurchase($attempt->test_purchase_id);

            $this->testPurchaseRepository->markAsCancelledIfNoActiveAttempts(
                purchaseId: $attempt->test_purchase_id,
                hasActiveAttempt: $hasActiveAttempt,
            );

            Log::channel('errors')->error('Stripe checkout session amount mismatch', [
                'stripe_event_id' => $event->id,
                'checkout_session_id' => $session->id,
                'payment_attempt_id' => $attempt->id,
                'expected_currency' => $attempt->currency,
                'stripe_currency' => $session->currency ?? null,
                'expected_amount_minor' => $this->minorAmount((float) $attempt->amount),
                'stripe_amount_total' => $session->amount_total ?? null,
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

        $paymentResult = $this->testPurchaseRepository->markAsPaidFromAttempt(
            purchase: $purchase,
            attempt: $attempt,
        );

        $updatedPurchase = $paymentResult['purchase'];

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

        TestPurchasePaid::dispatch(
            (int) $updatedPurchase->id,
            (int) $attempt->id,
            (string) $event->id,
        );

        if ($paymentResult['was_marked_as_paid_now'] === true) {
            $this->sendPurchasePaidNotifications(
                purchase: $updatedPurchase,
                attempt: $attempt,
                stripeEventId: (string)$event->id,
            );
        }
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

    private function sessionAmountMatchesAttempt(object $session, object $attempt): bool
    {
        $sessionCurrency = strtolower((string) ($session->currency ?? ''));
        $attemptCurrency = strtolower((string) $attempt->currency);
        $sessionAmount = $session->amount_total ?? null;

        if (! is_numeric($sessionAmount)) {
            return false;
        }

        return $sessionCurrency === $attemptCurrency
            && (int) $sessionAmount === $this->minorAmount((float) $attempt->amount);
    }

    private function minorAmount(float $amount): int
    {
        return (int) round($amount * 100);
    }

    private function sendPurchasePaidNotifications(object $purchase, object $attempt, string $stripeEventId,): void
    {
        $testLabel = "الاختبار رقم {$purchase->test_id}";

        $buyerPayload = NotificationPayload::make(
            title: 'تمت عملية الشراء بنجاح',
            body: "تم شراء {$testLabel} بنجاح، يمكنك الآن الوصول إلى الاختبار.",
            metadata: [
                'type' => 'test_purchase',
                'category' => 'payment',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#FFF2E7',
                    'icon' => ImageProcessor::urlOrDefault('system-notification/wallet.svg' , 'defaults/notification.svg' , 'public'),
                ],

                'actor' => null,

                'navigation' => [
                    'screen' => 'public_test_details',
                    'action' => 'open',
                ],

                'params' => [
                    'test_id' => (int) $purchase->test_id,
                ],
            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $purchase->buyer_user_id,
            payload: $buyerPayload,
        );


        $sellerUserId = $purchase->seller_user_id ?? null;

        if (! empty($sellerUserId) && (int) $sellerUserId !== (int) $purchase->buyer_user_id) {

            $sellerPayload = NotificationPayload::make(
                title: 'تم شراء اختبارك',
                body: "قام مستخدم بشراء {$testLabel}.",
                metadata: [
                    'type' => 'test_purchase',
                    'category' => 'payment',

                    'presentation' => [
                        'mode' => 'user',
                        'icon' => null,
                        'color' => null,
                    ],

                    'actor' => BuildActor::buildUserActor($sellerUserId),

                    'navigation' => [
                        'screen' => 'my_test_details',
                        'action' => 'open',
                    ],

                    'params' => [
                        'test_id' => (int) $purchase->test_id,
                        'buyer_user_id' => (int) $purchase->buyer_user_id,
                    ],
                ],
            );

            $this->notificationCenter->sendToUser(
                userId: (int) $sellerUserId,
                payload: $sellerPayload,
            );
        }
    }


}
