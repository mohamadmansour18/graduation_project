<?php

return [

    'default_provider' => env('PAYMENT_DEFAULT_PROVIDER', 'stripe'),

    'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'usd'),


    'platform_fee_percent' => (float) env('PAYMENT_PLATFORM_FEE_PERCENT', 10),


    'success_url' => env(
        'PAYMENT_SUCCESS_URL',
        env('APP_URL') . '/api/v1/payments/checkout/success?session_id={CHECKOUT_SESSION_ID}'
    ),

    'cancel_url' => env(
        'PAYMENT_CANCEL_URL',
        env('APP_URL') . '/api/v1/payments/checkout/cancel'
    ),

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'key' => env('STRIPE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

];
