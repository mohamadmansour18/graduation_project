<?php

namespace App\Services\Payments;

use App\Repositories\Payments\TestPurchaseRepository;
use Illuminate\Support\Facades\Log;
use Stripe\Event;

class StripeWebhookService
{
    public function __construct(
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
            Log::channel('daily')->info('Stripe checkout session completed but not paid', [
                'stripe_event_id' => $event->id,
                'checkout_session_id' => $session->id ?? null,
                'payment_status' => $session->payment_status ?? null,
            ]);

            return;
        }

        $result = $this->testPurchaseRepository->markPendingPurchaseAsPaidByReference($session->id);

        if (! $result['purchase']) {
            Log::channel('errors')->warning('Stripe paid checkout session has no matching purchase', [
                'stripe_event_id' => $event->id,
                'checkout_session_id' => $session->id,
            ]);

            return;
        }

        $purchase = $result['purchase'];

        if (! $result['was_marked_as_paid']) {
            Log::channel('daily')->info('Stripe checkout session already processed', [
                'stripe_event_id' => $event->id,
                'checkout_session_id' => $session->id,
                'purchase_id' => $purchase->id,
                'reason' => $result['reason'],
            ]);

            return;
        }

        Log::channel('audit')->info('Test purchase paid successfully', [
            'action' => 'test_purchase_paid',
            'purchase_id' => $purchase->id,
            'test_id' => $purchase->test_id,
            'buyer_user_id' => $purchase->buyer_user_id,
            'seller_user_id' => $purchase->seller_user_id,
            'gross_amount' => $purchase->gross_amount,
            'platform_fee_amount' => $purchase->platform_fee_amount,
            'seller_net_amount' => $purchase->seller_net_amount,
            'currency' => $purchase->currency,
            'payment_provider' => $purchase->payment_provider,
            'payment_reference' => $purchase->payment_reference,
            'stripe_event_id' => $event->id,
        ]);

        /*
        |--------------------------------------------------------------------------
        | مكان استدعاء Event الإشعارات لاحقًا
        |--------------------------------------------------------------------------
        |
        | هنا أصبح الدفع مثبتًا داخل قاعدة البيانات.
        | لذلك هذا هو المكان الصحيح لإطلاق Event يرسل إشعارًا للمشتري والبائع.
        |
        | مثال لاحقًا:
        |
        | TestPurchasePaid::dispatch($purchase);
        |
        | ثم تربط معه Listeners مثل:
        | - SendPurchaseSuccessNotificationToBuyer
        | - SendNewTestSaleNotificationToSeller
        | - UpdateFinancialStatsAfterTestPurchase
        |
        | يفضّل أن تكون هذه الـ Listeners queued حتى لا نبطئ رد webhook على Stripe.
        |
        */

    }

    private function handleCheckoutSessionExpired(Event $event): void
    {
        $session = $event->data->object;

        if (! isset($session->id)) {
            return;
        }

        $this->testPurchaseRepository
            ->markPendingPurchaseAsCancelledByReference($session->id);

        Log::channel('daily')->info('Stripe checkout session expired', [
            'stripe_event_id' => $event->id,
            'checkout_session_id' => $session->id,
        ]);
    }

    private function handlePaymentIntentFailed(Event $event): void
    {
        $paymentIntent = $event->data->object;

        Log::channel('daily')->info('Stripe payment intent failed', [
            'stripe_event_id' => $event->id,
            'payment_intent_id' => $paymentIntent->id ?? null,
            'last_payment_error_code' => $paymentIntent->last_payment_error->code ?? null,
            'last_payment_error_message' => $paymentIntent->last_payment_error->message ?? null,
        ]);
    }

    private function handleIgnoredEvent(Event $event): void
    {
        Log::channel('daily')->info('Stripe webhook event ignored', [
            'stripe_event_id' => $event->id,
            'event_type' => $event->type,
        ]);
    }
}
