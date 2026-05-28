<?php

namespace Tests\Unit;

use App\Services\AiQuestionGeneration\Support\AiProviderCapabilityService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AiProviderCapabilityServiceTest extends TestCase
{
    public function test_it_filters_unregistered_and_unsupported_providers_from_chain(): void
    {
        Config::set('ai_question_generation.providers', [
            'openrouter' => self::class,
            'deepseek' => self::class,
            'gemini' => self::class,
        ]);

        Config::set('ai_question_generation.provider_capabilities', [
            'openrouter' => [
                'source_types' => ['Images'],
                'input_modes' => ['raw_image'],
            ],
            'deepseek' => [
                'source_types' => ['Images', 'Pdf'],
                'input_modes' => ['extracted_text'],
            ],
            'gemini' => [
                'source_types' => ['Images', 'Pdf'],
                'input_modes' => ['raw_image', 'raw_file'],
            ],
            'ollama_cloud' => [
                'source_types' => ['Images'],
                'input_modes' => ['raw_image'],
            ],
        ]);
        Config::set('ai_question_generation.runtime_input_modes.Images', ['raw_image']);
        Config::set('ai_question_generation.runtime_input_modes.Pdf', ['raw_file']);

        $service = app(AiProviderCapabilityService::class);

        $chain = $service->filterProviderChain(
            providerNames: ['openrouter', 'ollama_cloud', 'deepseek', 'gemini', 'deepseek'],
            sourceType: 'Pdf'
        );

        $this->assertSame(['gemini'], $chain);
    }

    public function test_it_returns_registered_providers_that_support_source_type(): void
    {
        Config::set('ai_question_generation.providers', [
            'openrouter' => self::class,
            'deepseek' => self::class,
            'gemini' => self::class,
        ]);

        Config::set('ai_question_generation.provider_capabilities', [
            'openrouter' => [
                'source_types' => ['Images'],
                'input_modes' => ['raw_image'],
            ],
            'deepseek' => [
                'source_types' => ['Images', 'Pdf'],
                'input_modes' => ['extracted_text'],
            ],
            'gemini' => [
                'source_types' => ['Images', 'Pdf'],
                'input_modes' => ['raw_image', 'raw_file'],
            ],
        ]);
        Config::set('ai_question_generation.runtime_input_modes.Images', ['raw_image']);
        Config::set('ai_question_generation.runtime_input_modes.Pdf', ['raw_file']);

        $service = app(AiProviderCapabilityService::class);

        $this->assertSame(
            ['gemini'],
            $service->registeredProvidersSupporting('Pdf')
        );
    }
}
