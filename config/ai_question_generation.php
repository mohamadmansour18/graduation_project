<?php

use App\Services\AiQuestionGeneration\Providers\DeepSeekQuestionGenerationProvider;
use App\Services\AiQuestionGeneration\Providers\GeminiQuestionGenerationProvider;
use App\Services\AiQuestionGeneration\Providers\OllamaLocalQuestionGenerationProvider;
use App\Services\AiQuestionGeneration\Providers\OpenRouterQuestionGenerationProvider;

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

    'min_question_count' => 5,
    'max_question_count' => 40,

    /*
    |--------------------------------------------------------------------------
    | Daily Usage Limits
    |--------------------------------------------------------------------------
    */
    'verified_user_daily_limit' => 4,
    'unverified_user_daily_limit' => 2,

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
        'openrouter' => OpenRouterQuestionGenerationProvider::class,
        'deepseek' => DeepSeekQuestionGenerationProvider::class,
        'ollama_local' => OllamaLocalQuestionGenerationProvider::class,
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
| Provider Routing
|--------------------------------------------------------------------------
*/

    'provider_routing' => [
        'fallback_provider' => 'ollama_local',

        'chains' => [
            'low' => [
                'openrouter',
                'ollama_cloud',
                'deepseek',
                'gemini',
                'ollama_local',
            ],

            'medium' => [
                'openrouter',
                'gemini',
                'ollama_cloud',
                'deepseek',
                'ollama_local',
            ],

            'high' => [
                'gemini',
                'openrouter',
                'ollama_cloud',
                'deepseek',
                'ollama_local',
            ],
        ],

        'score_thresholds' => [
            'low_max' => 2,
            'medium_max' => 5,
        ],

        'scoring' => [
            'question_count' => [
                'more_than_10' => 1,
                'more_than_20' => 2,
                'more_than_30' => 3,
            ],

            'difficulty' => [
                'Easy' => 0,
                'Medium' => 1,
                'Hard' => 3,
            ],

            'source_type' => [
                'Images' => 1,
                'Pdf' => 2,
            ],

            'assets_count' => [
                'more_than_1' => 1,
                'more_than_2' => 2,
            ],

            'total_assets_size_kb' => [
                'more_than_1024' => 1,
                'more_than_4096' => 2,
                'more_than_8192' => 3,
            ],
        ],

        'cooldowns' => [
            'rate_limited_seconds' => 600,
            'temporary_unavailable_seconds' => 120,
            'connection_failed_seconds' => 60,
        ],
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

    /*
    |--------------------------------------------------------------------------
    | DeepSeek Provider
    |--------------------------------------------------------------------------
    */

    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY'),

        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),

        'model' => env('DEEPSEEK_MODEL', 'deepseek-v4-flash'),

        'timeout_seconds' => env('DEEPSEEK_TIMEOUT_SECONDS', 140),

        'temperature' => 0.3,

        'max_tokens' => 8192,

        'supported_source_types' => [
            'Images',
            'Pdf',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenRouter Provider
    |--------------------------------------------------------------------------
    */

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),

        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),

        'model' => env('OPENROUTER_MODEL', 'google/qwen3-vl-235b-a22b-instruct'),

        'timeout_seconds' => env('OPENROUTER_TIMEOUT_SECONDS', 180),

        'temperature' => 0.3,

        'max_tokens' => 8192,

        'supported_source_types' => [
            'Images',
        ],

        'app_name' => env('APP_NAME', 'AI Question Generation Platform'),

        'site_url' => env('APP_URL', 'http://localhost'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ollama Local Provider
    |--------------------------------------------------------------------------
    */

    'ollama_local' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://ollama:11434'),

        'model' => env('OLLAMA_MODEL', 'qwen3.5:4b'),

        'timeout_seconds' => env('OLLAMA_TIMEOUT_SECONDS', 180),

        'keep_alive' => env('OLLAMA_LOCAL_KEEP_ALIVE', '720h'),

        'num_thread' => env('OLLAMA_LOCAL_NUM_THREAD', 5),

        'num_ctx' => env('OLLAMA_LOCAL_NUM_CTX', 2048),

        'num_predict' => env('OLLAMA_LOCAL_NUM_PREDICT', 900),

        'supported_source_types' => [
            'Images',
        ],

        'temperature' => 0.3,
    ],


];
