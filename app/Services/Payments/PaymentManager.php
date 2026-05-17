<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentProviderInterface;
use App\Enums\Payments\PaymentProvider;
use App\Exceptions\Api\PaymentException;
use App\Services\Payments\Providers\StripeCheckoutProvider;

class PaymentManager
{
    public function __construct(
        private readonly StripeCheckoutProvider $stripeCheckoutProvider,
    ) {
    }

    public function driver(PaymentProvider $provider): PaymentProviderInterface
    {
        return match ($provider) {
            PaymentProvider::Stripe => $this->stripeCheckoutProvider,

            default => throw PaymentException::unsupportedPaymentProvider(),
        };
    }
}
