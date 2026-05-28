<?php

namespace Tests\Unit;

use App\Exceptions\Api\AiQuestionGenerationException;
use App\Models\AiQuestionGenerationAsset;
use App\Services\AiQuestionGeneration\Extraction\AiQuestionGenerationAssetTextExtractionService;
use App\Services\AiQuestionGeneration\Extraction\ExtractedAssetText;
use App\Services\AiQuestionGeneration\Extraction\ImageOcrTextExtractor;
use App\Services\AiQuestionGeneration\Extraction\PdfTextExtractor;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AiQuestionGenerationAssetTextExtractionServiceTest extends TestCase
{
    public function test_it_normalizes_extracted_text_spacing(): void
    {
        $service = $this->service();

        $text = $service->normalizeExtractedText("  first\t\tline\r\n\r\n\r\n second   line  ");

        $this->assertSame("first line\n\n second line", $text);
    }

    public function test_it_fails_when_extracted_text_is_too_short(): void
    {
        Config::set('ai_question_generation.text_extraction.min_extracted_text_chars', 10);

        $service = $this->service();

        $this->expectException(AiQuestionGenerationException::class);

        $service->prepareExtractedText(new AiQuestionGenerationAsset([
            'storage_path' => 'fake/path.pdf',
        ]), 'short');
    }

    public function test_it_limits_extracted_text_to_configured_size(): void
    {
        Config::set('ai_question_generation.text_extraction.min_extracted_text_chars', 1);
        Config::set('ai_question_generation.text_extraction.max_extracted_text_chars', 5);

        $service = $this->service();

        $text = $service->prepareExtractedText(new AiQuestionGenerationAsset([
            'storage_path' => 'fake/path.pdf',
        ]), '123456789');

        $this->assertSame('12345', $text);
    }

    public function test_extracted_asset_text_is_formatted_for_prompt(): void
    {
        $assetText = new ExtractedAssetText(
            assetId: 10,
            originalName: 'lesson.pdf',
            mimeType: 'application/pdf',
            text: 'Educational content.'
        );

        $this->assertSame(
            "File: lesson.pdf\nMime-Type: application/pdf\n\nEducational content.",
            $assetText->formattedForPrompt()
        );
    }

    private function service(): AiQuestionGenerationAssetTextExtractionService
    {
        return new AiQuestionGenerationAssetTextExtractionService(
            pdfTextExtractor: $this->createMock(PdfTextExtractor::class),
            imageOcrTextExtractor: $this->createMock(ImageOcrTextExtractor::class)
        );
    }
}
