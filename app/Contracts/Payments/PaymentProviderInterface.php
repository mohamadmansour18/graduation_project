<?php

namespace App\Contracts\Payments;

use App\DTOs\Payments\CheckoutSessionResult;
use App\DTOs\Payments\CreateCheckoutSessionData;

interface PaymentProviderInterface
{
    public function createCheckoutSession(CreateCheckoutSessionData $data): CheckoutSessionResult;
}
