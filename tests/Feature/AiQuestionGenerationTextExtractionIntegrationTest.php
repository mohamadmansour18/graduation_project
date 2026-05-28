<?php

namespace Tests\Feature;

use App\Models\AiQuestionGenerationAsset;
use App\Services\AiQuestionGeneration\Extraction\ImageOcrTextExtractor;
use App\Services\AiQuestionGeneration\Extraction\PdfTextExtractor;
use Illuminate\Support\Facades\Config;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AiQuestionGenerationTextExtractionIntegrationTest extends TestCase
{
    /** @var array<int, string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $filePath) {
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }

        parent::tearDown();
    }

    public function test_pdf_text_extractor_reads_generated_pdf_content(): void
    {
        $this->skipIfBinaryIsMissing('pdftotext');

        Config::set('ai_question_generation.text_extraction.pdf.binary', 'pdftotext');
        Config::set('ai_question_generation.text_extraction.pdf.timeout_seconds', 10);

        $pdfPath = $this->createTemporaryPdf('NERD AI PDF EXTRACTION SAMPLE');

        $text = app(PdfTextExtractor::class)->extract(
            asset: new AiQuestionGenerationAsset([
                'storage_path' => 'tmp/sample.pdf',
                'mime_type' => 'application/pdf',
            ]),
            filePath: $pdfPath
        );

        $this->assertStringContainsString('NERD AI PDF EXTRACTION SAMPLE', $text);
    }

    public function test_pdf_text_extractor_uses_ocr_fallback_for_image_only_pdf(): void
    {
        $this->skipIfBinaryIsMissing('pdftotext');
        $this->skipIfBinaryIsMissing('pdftoppm');
        $this->skipIfBinaryIsMissing('tesseract');
        $this->skipIfTesseractLanguageIsMissing('eng');

        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required to generate OCR sample image.');
        }

        Config::set('ai_question_generation.text_extraction.min_extracted_text_chars', 10);
        Config::set('ai_question_generation.text_extraction.pdf.binary', 'pdftotext');
        Config::set('ai_question_generation.text_extraction.pdf.timeout_seconds', 10);
        Config::set('ai_question_generation.text_extraction.pdf.render_binary', 'pdftoppm');
        Config::set('ai_question_generation.text_extraction.pdf.render_timeout_seconds', 20);
        Config::set('ai_question_generation.text_extraction.pdf.ocr_fallback_enabled', true);
        Config::set('ai_question_generation.text_extraction.pdf.ocr_render_dpi', 220);
        Config::set('ai_question_generation.text_extraction.pdf.ocr_max_pages', 1);
        Config::set('ai_question_generation.text_extraction.ocr.binary', 'tesseract');
        Config::set('ai_question_generation.text_extraction.ocr.timeout_seconds', 20);
        Config::set('ai_question_generation.text_extraction.ocr.languages', 'eng');
        Config::set('ai_question_generation.text_extraction.ocr.page_segmentation_mode', 6);

        $imagePath = $this->createTemporaryImage('SCANNED PDF TEXT 123');
        $pdfPath = $this->createTemporaryImageOnlyPdf($imagePath);

        $text = app(PdfTextExtractor::class)->extract(
            asset: new AiQuestionGenerationAsset([
                'storage_path' => 'tmp/scanned-sample.pdf',
                'mime_type' => 'application/pdf',
            ]),
            filePath: $pdfPath
        );

        $normalizedText = strtoupper(preg_replace('/[^A-Z0-9]+/i', '', $text) ?? '');

        $this->assertStringContainsString('SCANNED', $normalizedText);
        $this->assertStringContainsString('PDF', $normalizedText);
        $this->assertStringContainsString('123', $normalizedText);
    }

    public function test_image_ocr_extractor_reads_generated_image_content(): void
    {
        $this->skipIfBinaryIsMissing('tesseract');
        $this->skipIfTesseractLanguageIsMissing('eng');

        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required to generate OCR sample image.');
        }

        Config::set('ai_question_generation.text_extraction.ocr.binary', 'tesseract');
        Config::set('ai_question_generation.text_extraction.ocr.timeout_seconds', 15);
        Config::set('ai_question_generation.text_extraction.ocr.languages', 'eng');
        Config::set('ai_question_generation.text_extraction.ocr.page_segmentation_mode', 6);

        $imagePath = $this->createTemporaryImage('TEST 123');

        $text = app(ImageOcrTextExtractor::class)->extract(
            asset: new AiQuestionGenerationAsset([
                'storage_path' => 'tmp/sample.png',
                'mime_type' => 'image/png',
            ]),
            filePath: $imagePath
        );

        $normalizedText = strtoupper(preg_replace('/[^A-Z0-9]+/i', '', $text) ?? '');

        $this->assertStringContainsString('TEST', $normalizedText);
        $this->assertStringContainsString('123', $normalizedText);
    }

    private function skipIfBinaryIsMissing(string $binary): void
    {
        $process = new Process(['which', $binary]);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->markTestSkipped("{$binary} binary is not available in this environment.");
        }
    }

    private function skipIfTesseractLanguageIsMissing(string $language): void
    {
        $process = new Process(['tesseract', '--list-langs']);
        $process->run();

        if (! $process->isSuccessful() || ! str_contains($process->getOutput(), $language)) {
            $this->markTestSkipped("Tesseract language [{$language}] is not available in this environment.");
        }
    }

    private function createTemporaryPdf(string $content): string
    {
        $pdfPath = $this->temporaryFilePath('ai-question-generation-', '.pdf');
        $tempDirectory = sys_get_temp_dir() . '/ai-question-generation-mpdf';

        if (! is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0775, true);
        }

        $mpdf = new Mpdf([
            'tempDir' => $tempDirectory,
        ]);

        $mpdf->WriteHTML('<h1>' . e($content) . '</h1><p>This file is generated during tests.</p>');
        $mpdf->Output($pdfPath, Destination::FILE);

        return $pdfPath;
    }

    private function createTemporaryImageOnlyPdf(string $imagePath): string
    {
        $pdfPath = $this->temporaryFilePath('ai-question-generation-scanned-', '.pdf');
        $tempDirectory = sys_get_temp_dir() . '/ai-question-generation-mpdf';

        if (! is_dir($tempDirectory)) {
            mkdir($tempDirectory, 0775, true);
        }

        $mpdf = new Mpdf([
            'tempDir' => $tempDirectory,
        ]);

        $mpdf->WriteHTML('<img src="' . e($imagePath) . '" style="width: 100%;">');
        $mpdf->Output($pdfPath, Destination::FILE);

        return $pdfPath;
    }

    private function createTemporaryImage(string $content): string
    {
        $imagePath = $this->temporaryFilePath('ai-question-generation-', '.png');

        $smallImage = imagecreatetruecolor(360, 100);
        $white = imagecolorallocate($smallImage, 255, 255, 255);
        $black = imagecolorallocate($smallImage, 0, 0, 0);

        imagefilledrectangle($smallImage, 0, 0, 359, 99, $white);
        imagestring($smallImage, 5, 30, 35, $content, $black);

        $largeImage = imagecreatetruecolor(1440, 400);
        $largeWhite = imagecolorallocate($largeImage, 255, 255, 255);
        imagefilledrectangle($largeImage, 0, 0, 1439, 399, $largeWhite);
        imagecopyresampled($largeImage, $smallImage, 0, 0, 0, 0, 1440, 400, 360, 100);

        imagepng($largeImage, $imagePath);

        imagedestroy($smallImage);
        imagedestroy($largeImage);

        return $imagePath;
    }

    private function temporaryFilePath(string $prefix, string $extension): string
    {
        $filePath = tempnam(sys_get_temp_dir(), $prefix);

        if ($filePath === false) {
            $this->fail('Unable to create temporary file.');
        }

        $targetPath = $filePath . $extension;
        rename($filePath, $targetPath);

        $this->temporaryFiles[] = $targetPath;

        return $targetPath;
    }
}
