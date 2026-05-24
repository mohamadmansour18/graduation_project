<?php

use App\Services\AiQuestionGeneration\Providers\GeminiQuestionGenerationProvider;

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
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'duplicate_cache_ttl_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Provider Settings
    |--------------------------------------------------------------------------
    */

    'default_provider' => env('AI_QUESTION_GENERATION_PROVIDER', 'gemini'),

    'providers' => [
        'gemini' => GeminiQuestionGenerationProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Local File Validation
    |--------------------------------------------------------------------------
    */
    'local_validation' => [
        'image_sample_size' => 64,

        'min_image_width' => 80,
        'min_image_height' => 80,
        'max_image_pixels' => 24_000_000,

        'blank_brightness_low' => 8,
        'blank_brightness_high' => 247,
        'blank_stddev_threshold' => 2.5,
        'blank_range_threshold' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Failure Messages
    |--------------------------------------------------------------------------
    */
    'failure_messages' => [
        'AI_PROVIDER_RATE_LIMITED' => 'خدمة الذكاء الاصطناعي مشغولة حالياً، يرجى المحاولة بعد قليل',
        'AI_PROVIDER_REQUEST_FAILED' => 'فشل طلب خدمة الذكاء الاصطناعي، يرجى المحاولة لاحقاً',
        'AI_PROVIDER_INVALID_RESPONSE' => 'تم استلام نتيجة غير صالحة من خدمة الذكاء الاصطناعي، يرجى المحاولة مرة أخرى',
        'AI_PROVIDER_EMPTY_RESPONSE' => 'لم ترجع خدمة الذكاء الاصطناعي أي نتيجة قابلة للاستخدام، يرجى المحاولة مرة أخرى',
        'AI_TEMPORARY_FILE_MISSING' => 'تعذر الوصول إلى الملفات المؤقتة الخاصة بطلب التوليد، يرجى إعادة رفع الملفات',
        'AI_TEMPORARY_FILE_READ_FAILED' => 'تعذر قراءة الملفات المؤقتة الخاصة بطلب التوليد، يرجى إعادة رفع الملفات',
        'AI_CONTENT_NOT_EDUCATIONAL' => 'المحتوى المرفوع لا يبدو محتوى علمياً أو تعليمياً مناسباً لتوليد أسئلة منه',
        'AI_NOT_ENOUGH_EDUCATIONAL_CONTENT' => 'المحتوى المرفوع لا يحتوي على معلومات كافية لتوليد العدد المطلوب من الأسئلة',
        'AI_INVALID_GENERATED_QUESTIONS' => 'تم توليد أسئلة غير صالحة من خدمة الذكاء الاصطناعي، يرجى المحاولة مرة أخرى',
        'AI_GENERATION_FAILED' => 'فشل توليد الأسئلة، يرجى المحاولة لاحقاً',
    ],

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
