<?php

return [

    'default_provider' => env('PAYMENT_DEFAULT_PROVIDER', 'stripe'),

    'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'usd'),

    'pricing_currency' => env('PAYMENT_PRICING_CURRENCY', 'syp'),


    'platform_fee_percent' => (float) env('PAYMENT_PLATFORM_FEE_PERCENT', 20),

    'minimum_checkout_amount' => (float) env('PAYMENT_MINIMUM_CHECKOUT_AMOUNT', 0.50),
    'minimum_checkout_safety_margin_percent' => (float) env('PAYMENT_MINIMUM_CHECKOUT_SAFETY_MARGIN_PERCENT', 20),
    'max_test_price' => (float) env('PAYMENT_MAX_TEST_PRICE', 10000000),

    'checkout_session_expires_after_minutes' => (int) env(
        'PAYMENT_CHECKOUT_SESSION_EXPIRES_AFTER_MINUTES',
        30
    ),

    'success_url' => env(
        'PAYMENT_SUCCESS_URL',
        env('APP_URL') . '/payment/return/success?session_id={CHECKOUT_SESSION_ID}'
    ),

    'cancel_url' => env(
        'PAYMENT_CANCEL_URL',
        env('APP_URL') . '/payment/return/cancel'
    ),

    'app_deep_link_scheme' => env('PAYMENT_APP_DEEP_LINK_SCHEME', 'nerd'),

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'key' => env('STRIPE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'checkout_currency' => env('STRIPE_CHECKOUT_CURRENCY', 'usd'),
    ],

    'currency_conversion' => [
        'provider' => env('PAYMENT_EXCHANGE_RATE_PROVIDER', 'exchange_rate_api'),
        'timeout_seconds' => (int) env('PAYMENT_EXCHANGE_RATE_TIMEOUT_SECONDS', 8),
        'connect_timeout_seconds' => (int) env('PAYMENT_EXCHANGE_RATE_CONNECT_TIMEOUT_SECONDS', 4),
        'cache_fallback_minutes' => (int) env('PAYMENT_EXCHANGE_RATE_CACHE_FALLBACK_MINUTES', 15),
        'fallback_max_stale_days' => (int) env('PAYMENT_EXCHANGE_RATE_FALLBACK_MAX_STALE_DAYS', 3),

        'market' => [
            'timezone' => env('PAYMENT_EXCHANGE_RATE_MARKET_TIMEZONE', 'Asia/Damascus'),
            'opens_at_hour' => (int) env('PAYMENT_EXCHANGE_RATE_MARKET_OPENS_AT_HOUR', 10),
            'open_weekdays' => [0, 1, 2, 3, 4],
        ],

        'providers' => [
            'exchange_rate_api' => [
                'key' => env('EXCHANGE_RATE_API_KEY'),
                'base_url' => env('EXCHANGE_RATE_API_BASE_URL', 'https://v6.exchangerate-api.com/v6'),
            ],
        ],

        'manual_fallback' => [
            'syp_per_usd' => env('PAYMENT_MANUAL_SYP_PER_USD'),
            'rates' => [],
        ],
    ],

];
