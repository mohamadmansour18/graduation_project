<?php

namespace App\DTOs\Payments;

class CheckoutSessionResult
{
    public function __construct(
        public string $provider,
        public string $checkoutSessionId,
        public string $checkoutUrl,
        public ?string $paymentIntentId = null,
    ) {
    }
}
