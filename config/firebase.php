<?php

declare(strict_types=1);

return [
    /*
     * ------------------------------------------------------------------------
     * Default Firebase project
     * ------------------------------------------------------------------------
     */

    'default' => env('FIREBASE_PROJECT', 'mobile'),

    /*
     * ------------------------------------------------------------------------
     * Firebase project configurations
     * ------------------------------------------------------------------------
     */

    'projects' => [

        /*
         * ------------------------------------------------------------------------
         * Mobile Firebase Project
         * ------------------------------------------------------------------------
         *
         * هذا المشروع مخصص لإشعارات تطبيق الموبايل.
         */

        'mobile' => [

            'credentials' => env(
                'FIREBASE_MOBILE_CREDENTIALS',
                env('GOOGLE_APPLICATION_CREDENTIALS')
            ),

            'auth' => [
                'tenant_id' => env('FIREBASE_MOBILE_AUTH_TENANT_ID'),
            ],

            'firestore' => [
                // 'database' => env('FIREBASE_MOBILE_FIRESTORE_DATABASE'),
            ],

            'database' => [
                'url' => env('FIREBASE_MOBILE_DATABASE_URL'),
            ],

            'dynamic_links' => [
                'default_domain' => env('FIREBASE_MOBILE_DYNAMIC_LINKS_DEFAULT_DOMAIN'),
            ],

            'storage' => [
                'default_bucket' => env('FIREBASE_MOBILE_STORAGE_DEFAULT_BUCKET'),
            ],

            'cache_store' => env('FIREBASE_CACHE_STORE', 'redis'),

            'logging' => [
                'http_log_channel' => env('FIREBASE_HTTP_LOG_CHANNEL'),
                'http_debug_log_channel' => env('FIREBASE_HTTP_DEBUG_LOG_CHANNEL'),
            ],

            'http_client_options' => [
                'proxy' => env('FIREBASE_HTTP_CLIENT_PROXY'),
                'timeout' => env('FIREBASE_HTTP_CLIENT_TIMEOUT', 10),
                'guzzle_middlewares' => [],
            ],
        ],

        /*
         * ------------------------------------------------------------------------
         * Web Firebase Project
         * ------------------------------------------------------------------------
         *
         * هذا المشروع مخصص لإشعارات الويب.
         */

        'web' => [

            'credentials' => env(
                'FIREBASE_WEB_CREDENTIALS',
                env('GOOGLE_APPLICATION_CREDENTIALS')
            ),

            'auth' => [
                'tenant_id' => env('FIREBASE_WEB_AUTH_TENANT_ID'),
            ],

            'firestore' => [
                // 'database' => env('FIREBASE_WEB_FIRESTORE_DATABASE'),
            ],

            'database' => [
                'url' => env('FIREBASE_WEB_DATABASE_URL'),
            ],

            'dynamic_links' => [
                'default_domain' => env('FIREBASE_WEB_DYNAMIC_LINKS_DEFAULT_DOMAIN'),
            ],

            'storage' => [
                'default_bucket' => env('FIREBASE_WEB_STORAGE_DEFAULT_BUCKET'),
            ],

            'cache_store' => env('FIREBASE_CACHE_STORE', 'redis'),

            'logging' => [
                'http_log_channel' => env('FIREBASE_HTTP_LOG_CHANNEL'),
                'http_debug_log_channel' => env('FIREBASE_HTTP_DEBUG_LOG_CHANNEL'),
            ],

            'http_client_options' => [
                'proxy' => env('FIREBASE_HTTP_CLIENT_PROXY'),
                'timeout' => env('FIREBASE_HTTP_CLIENT_TIMEOUT', 10),
                'guzzle_middlewares' => [],
            ],
        ],
    ],
];
