<?php

namespace App\Services\Payments\Providers;

use App\Contracts\Payments\PaymentProviderInterface;
use App\DTOs\Payments\CreateCheckoutSessionData;
use App\DTOs\Payments\CheckoutSessionResult;
use App\Enums\Payments\PaymentProvider;
use Stripe\StripeClient;

class StripeCheckoutProvider implements PaymentProviderInterface
{
    public function createCheckoutSession(CreateCheckoutSessionData $data): CheckoutSessionResult
    {
        $stripe = new StripeClient(config('payments.stripe.secret'));

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment', // one time , not subscription because user buy test once time

            'line_items' => [
                [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => $data->money->currency,
                        'unit_amount' => $data->money->grossAmountInMinorUnit(),
                        'product_data' => [
                            'name' => $data->testTitle,
                            'description' => 'شراء وصول دائم لاختبار داخل التطبيق',
                        ],
                    ],
                ],
            ],

            'success_url' => $data->successUrl,
            'cancel_url' => $data->cancelUrl,

            'metadata' => array_merge($data->metadata, [
                'purchase_id' => (string) $data->purchaseId,
                'test_id' => (string) $data->testId,
                'buyer_user_id' => (string) $data->buyerUserId,
                'seller_user_id' => (string) $data->sellerUserId,
            ]),
        ]);

        return new CheckoutSessionResult(
            provider: PaymentProvider::Stripe->value,
            checkoutSessionId: $session->id,
            checkoutUrl: $session->url,
            paymentIntentId: is_string($session->payment_intent) ? $session->payment_intent : null,
        );
    }

}
