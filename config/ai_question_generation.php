<?php

use App\Services\AiQuestionGeneration\Providers\DeepSeekQuestionGenerationProvider;
use App\Services\AiQuestionGeneration\Providers\GeminiQuestionGenerationProvider;
use App\Services\AiQuestionGeneration\Providers\HuggingFaceInferenceQuestionGenerationProvider;
use App\Services\AiQuestionGeneration\Providers\OllamaCloudQuestionGenerationProvider;
use App\Services\AiQuestionGeneration\Providers\OllamaLocalQuestionGenerationProvider;
use App\Services\AiQuestionGeneration\Providers\OpenRouterQuestionGenerationProvider;
use App\Services\AiQuestionGeneration\Providers\CloudflareWorkersAiQuestionGenerationProvider;

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
    'queue_name' => env('AI_QUESTION_GENERATION_QUEUE', 'heavy'),


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
        'cloudflare_workers_ai' => CloudflareWorkersAiQuestionGenerationProvider::class,
        'huggingface' => HuggingFaceInferenceQuestionGenerationProvider::class,
        'ollama_local' => OllamaLocalQuestionGenerationProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Capabilities
    |--------------------------------------------------------------------------
    |
    | These capabilities describe what each provider can receive from this
    | feature. Providers listed in routing chains but not registered above are
    | ignored until their provider class is implemented and added.
    |
    */

    'provider_capabilities' => [
        'gemini' => [
            'source_types' => ['Images', 'Pdf'],
            'input_modes' => ['raw_image', 'raw_file'],
        ],

        'openrouter' => [
            'source_types' => ['Images'],
            'input_modes' => ['raw_image'],
        ],

        'cloudflare_workers_ai' => [
            'source_types' => ['Images', 'Pdf'],
            'input_modes' => ['raw_image', 'raw_file'],
        ],

        'ollama_local' => [
            'source_types' => ['Images'],
            'input_modes' => ['raw_image'],
        ],

        'ollama_cloud' => [
            'source_types' => ['Images', 'Pdf'],
            'input_modes' => ['extracted_text'],
        ],

        'huggingface' => [
            'source_types' => ['Images', 'Pdf'],
            'input_modes' => ['extracted_text'],
        ],
    ],

    'runtime_input_modes' => [
        'Images' => ['raw_image', 'extracted_text'],
        'Pdf' => ['raw_file', 'extracted_text'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Text Extraction
    |--------------------------------------------------------------------------
    */

    'text_extraction' => [
        'min_extracted_text_chars' => 40,
        'max_extracted_text_chars' => 60_000,

        'pdf' => [
            'binary' => env('AI_PDF_TEXT_BINARY', 'pdftotext'),
            'timeout_seconds' => env('AI_PDF_TEXT_TIMEOUT_SECONDS', 30),
            'render_binary' => env('AI_PDF_RENDER_BINARY', 'pdftoppm'),
            'render_timeout_seconds' => env('AI_PDF_RENDER_TIMEOUT_SECONDS', 60),
            'ocr_fallback_enabled' => env('AI_PDF_OCR_FALLBACK_ENABLED', true),
            'ocr_render_dpi' => env('AI_PDF_OCR_RENDER_DPI', 220),
            'ocr_max_pages' => env('AI_PDF_OCR_MAX_PAGES', 20),
        ],

        'ocr' => [
            'binary' => env('AI_OCR_BINARY', 'tesseract'),
            'timeout_seconds' => env('AI_OCR_TIMEOUT_SECONDS', 45),
            'languages' => env('AI_OCR_LANGUAGES', 'ara+eng'),
            'page_segmentation_mode' => env('AI_OCR_PSM', 6),
        ],
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
                'cloudflare_workers_ai',
                'huggingface',
                'gemini',
                'ollama_local',
            ],

            'medium' => [
                'openrouter',
                'gemini',
                'cloudflare_workers_ai',
                'huggingface',
                'ollama_local',
            ],

            'high' => [
                'gemini',
                'openrouter',
                'cloudflare_workers_ai',
                'huggingface',
                'ollama_local',
            ],
        ],

        'chains_by_source_type' => [
            'Images' => [
                'low' => [
                    'openrouter',
                    'cloudflare_workers_ai',
                    'gemini',
                    'huggingface',
                    'ollama_local',
                ],

                'medium' => [
                    'openrouter',
                    'cloudflare_workers_ai',
                    'gemini',
                    'huggingface',
                    'ollama_local',
                ],

                'high' => [
                    'gemini',
                    'openrouter',
                    'cloudflare_workers_ai',
                    'huggingface',
                    'ollama_local',
                ],
            ],

            'Pdf' => [
                'low' => [
                    'cloudflare_workers_ai',
                    'gemini',
                    'huggingface',
                ],

                'medium' => [
                    'cloudflare_workers_ai',
                    'gemini',
                    'huggingface',
                ],

                'high' => [
                    'gemini',
                    'cloudflare_workers_ai',
                    'huggingface',
                ],
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
    | Cloudflare Workers AI Provider
    |--------------------------------------------------------------------------
    */

    'cloudflare_workers_ai' => [
        'api_key' => env('CLOUDFLARE_AI_API_KEY'),

        'account_id' => env('CLOUDFLARE_ACCOUNT_ID'),

        'base_url' => env('CLOUDFLARE_AI_BASE_URL', 'https://api.cloudflare.com/client/v4'),

        'model' => env('CLOUDFLARE_AI_MODEL', '@cf/google/gemma-4-26b-a4b-it'),

        'timeout_seconds' => env('CLOUDFLARE_AI_TIMEOUT_SECONDS', 180),

        'temperature' => 0.1,

        'max_tokens' => env('CLOUDFLARE_AI_MAX_TOKENS', 8192),

        'supported_source_types' => [
            'Images',
            'Pdf',
        ],
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

    /*
    |--------------------------------------------------------------------------
    | Ollama Cloud Provider
    |--------------------------------------------------------------------------
    */

    'ollama_cloud' => [
        'api_key' => env('OLLAMA_API_KEY'),

        'base_url' => env('OLLAMA_CLOUD_BASE_URL', 'https://ollama.com'),

        'model' => env('OLLAMA_CLOUD_MODEL', 'gpt-oss:120b'),

        'timeout_seconds' => env('OLLAMA_CLOUD_TIMEOUT_SECONDS', 180),

        'temperature' => 0.3,

        'num_ctx' => env('OLLAMA_CLOUD_NUM_CTX', 4096),

        'num_predict' => env('OLLAMA_CLOUD_NUM_PREDICT', 8192),

        'supported_source_types' => [
            'Images',
            'Pdf',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hugging Face Inference Provider
    |--------------------------------------------------------------------------
    */

    'huggingface' => [
        'api_key' => env('HUGGINGFACE_API_KEY', env('HF_TOKEN')),

        'base_url' => env('HUGGINGFACE_BASE_URL', 'https://router.huggingface.co/v1'),

        'model' => env('HUGGINGFACE_MODEL', 'openai/gpt-oss-20b:cheapest'),

        'timeout_seconds' => env('HUGGINGFACE_TIMEOUT_SECONDS', 180),

        'temperature' => 0.3,

        'max_tokens' => env('HUGGINGFACE_MAX_TOKENS', 8192),

        'bill_to' => env('HUGGINGFACE_BILL_TO'),

        'supported_source_types' => [
            'Images',
            'Pdf',
        ],
    ],


];
