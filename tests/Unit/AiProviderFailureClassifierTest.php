<?php

namespace Tests\Unit;

use App\Exceptions\Api\AiQuestionGenerationException;
use App\Services\AiQuestionGeneration\Support\AiProviderFailureClassifier;
use Tests\TestCase;

class AiProviderFailureClassifierTest extends TestCase
{
    public function test_provider_not_enough_content_can_try_next_provider(): void
    {
        $decision = app(AiProviderFailureClassifier::class)->classify(
            AiQuestionGenerationException::notEnoughEducationalContent(
                minimumRequired: 3,
                generatedCount: 0
            )
        );

        $this->assertTrue($decision->shouldTryNextProvider);
        $this->assertSame('PROVIDER_NOT_ENOUGH_EDUCATIONAL_CONTENT', $decision->failureCode);
        $this->assertNull($decision->cooldownSeconds);
    }

    public function test_provider_content_not_educational_can_try_next_provider(): void
    {
        $decision = app(AiProviderFailureClassifier::class)->classify(
            AiQuestionGenerationException::contentIsNotEducational()
        );

        $this->assertTrue($decision->shouldTryNextProvider);
        $this->assertSame('CONTENT_NOT_EDUCATIONAL', $decision->failureCode);
        $this->assertNull($decision->cooldownSeconds);
    }

    public function test_provider_auth_failure_can_try_next_provider(): void
    {
        $decision = app(AiProviderFailureClassifier::class)->classify(
            AiQuestionGenerationException::providerRequestFailed(
                provider: 'CloudflareWorkersAI',
                operation: 'run',
                status: 403,
                responseBody: 'Model Agreement required.'
            )
        );

        $this->assertTrue($decision->shouldTryNextProvider);
        $this->assertSame('AI_PROVIDER_AUTH_FAILED', $decision->failureCode);
        $this->assertNull($decision->cooldownSeconds);
    }

    public function test_text_extraction_failure_can_try_next_provider(): void
    {
        $decision = app(AiProviderFailureClassifier::class)->classify(
            AiQuestionGenerationException::assetTextExtractionFailed(
                path: 'fake/path.pdf',
                reason: 'pdftotext is unavailable.'
            )
        );

        $this->assertTrue($decision->shouldTryNextProvider);
        $this->assertSame('AI_ASSET_TEXT_EXTRACTION_FAILED', $decision->failureCode);
        $this->assertNull($decision->cooldownSeconds);
    }
}
