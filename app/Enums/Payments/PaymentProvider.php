<?php

namespace App\Enums\Payments;

enum PaymentProvider: string
{
    case Stripe = 'stripe';
    case GooglePlay = 'google_play';
    case AppleInAppPurchase = 'apple_iap';
    case Demo = 'demo';
}
