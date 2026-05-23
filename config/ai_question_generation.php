<?php

return [

    'storage_disk' => env('AI_QUESTION_GENERATION_DISK', 'public'),

    'temporary_directory' => 'ai-question-generations',

    /*
    |--------------------------------------------------------------------------
    | Request Limits
    |--------------------------------------------------------------------------
    */
    'max_images_count' => 3,
    'min_images_count' => 1,

    'max_image_size_kb' => 5 * 1024,
    'max_pdf_size_kb' => 10 * 1024,

    'min_question_count' => 10,
    'max_question_count' => 40,

    /*
    |--------------------------------------------------------------------------
    | Daily Usage Limits
    |--------------------------------------------------------------------------
    */
    'verified_user_daily_limit' => 6,
    'unverified_user_daily_limit' => 3,

    /*
    |--------------------------------------------------------------------------
    | Polling / Jobs
    |--------------------------------------------------------------------------
    */
    'queue_name' => env('AI_QUESTION_GENERATION_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Gemini Provider
    |--------------------------------------------------------------------------
    */

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),

        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash'),

        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),

        'timeout_seconds' => env('GEMINI_TIMEOUT_SECONDS', 100),
    ],

];
