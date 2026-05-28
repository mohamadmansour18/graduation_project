<?php

namespace App\Services\AiQuestionGeneration\Extraction;

use App\Exceptions\Api\AiQuestionGenerationException;
use App\Models\AiQuestionGenerationAsset;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class PdfTextExtractor
{
    public function extract(AiQuestionGenerationAsset $asset, string $filePath): string
    {
        $pdftotextResult = $this->extractBestTextWithPdfToText($asset, $filePath);

        if ($this->isUsableText($pdftotextResult['text'])) {
            return $pdftotextResult['text'];
        }

        if (! (bool) config('ai_question_generation.text_extraction.pdf.ocr_fallback_enabled', true)) {
            throw AiQuestionGenerationException::assetTextExtractionFailed(
                path: $asset->storage_path,
                reason: $this->buildInsufficientTextReason($pdftotextResult['text'], '', $pdftotextResult['attempts'])
            );
        }

        $ocrText = $this->extractTextWithOcrFallback($asset, $filePath);

        if ($this->isUsableText($ocrText)) {
            return $ocrText;
        }

        throw AiQuestionGenerationException::assetTextExtractionFailed(
            path: $asset->storage_path,
            reason: $this->buildInsufficientTextReason($pdftotextResult['text'], $ocrText, $pdftotextResult['attempts'])
        );
    }

    /**
     * @return array{text: string, attempts: array<int, array<string, mixed>>}
     */
    private function extractBestTextWithPdfToText(AiQuestionGenerationAsset $asset, string $filePath): array
    {
        $binary = (string) config('ai_question_generation.text_extraction.pdf.binary', 'pdftotext');
        $timeout = (int) config('ai_question_generation.text_extraction.pdf.timeout_seconds', 30);
        $bestText = '';
        $attempts = [];

        foreach ($this->pdftotextStrategies() as $strategy) {
            $process = new Process([
                $binary,
                ...$strategy['options'],
                '-enc',
                'UTF-8',
                '-eol',
                'unix',
                '-nopgbrk',
                $filePath,
                '-',
            ]);
            $process->setTimeout($timeout);

            try {
                $process->run();
            } catch (ProcessTimedOutException) {
                $attempts[] = $this->attemptDetails($strategy['name'], false, '', 'PDF text extraction timed out.');
                continue;
            } catch (Throwable $exception) {
                $attempts[] = $this->attemptDetails($strategy['name'], false, '', $exception->getMessage());
                continue;
            }

            $output = $process->isSuccessful() ? $process->getOutput() : '';
            $error = $process->isSuccessful()
                ? null
                : (trim($process->getErrorOutput()) ?: 'PDF text extraction process failed.');

            $attempts[] = $this->attemptDetails($strategy['name'], $process->isSuccessful(), $output, $error);

            if ($this->normalizedTextLength($output) > $this->normalizedTextLength($bestText)) {
                $bestText = $output;
            }
        }

        Log::info('AI PDF pdftotext extraction finished.', [
            'asset_id' => $asset->id,
            'storage_path' => $asset->storage_path,
            'best_text_chars' => $this->normalizedTextLength($bestText),
            'attempts' => $attempts,
        ]);

        return [
            'text' => $bestText,
            'attempts' => $attempts,
        ];
    }

    private function extractTextWithOcrFallback(AiQuestionGenerationAsset $asset, string $filePath): string
    {
        $tempDirectory = $this->makeTempDirectory();

        try {
            $renderedPages = $this->renderPdfPages($asset, $filePath, $tempDirectory);
            $pageTexts = [];
            $pageAttempts = [];

            foreach ($renderedPages as $index => $pagePath) {
                $pageNumber = $index + 1;
                $pageText = $this->ocrPage($pagePath);
                $pageTexts[] = trim($pageText);
                $pageAttempts[] = [
                    'page' => $pageNumber,
                    'text_chars' => $this->normalizedTextLength($pageText),
                ];
            }

            $text = trim(implode("\n\n", array_filter($pageTexts)));

            Log::info('AI PDF OCR fallback finished.', [
                'asset_id' => $asset->id,
                'storage_path' => $asset->storage_path,
                'pages_processed' => count($renderedPages),
                'text_chars' => $this->normalizedTextLength($text),
                'page_attempts' => $pageAttempts,
            ]);

            return $text;
        } finally {
            $this->deleteDirectory($tempDirectory);
        }
    }

    /**
     * @return array<int, string>
     */
    private function renderPdfPages(AiQuestionGenerationAsset $asset, string $filePath, string $tempDirectory): array
    {
        $binary = (string) config('ai_question_generation.text_extraction.pdf.render_binary', 'pdftoppm');
        $timeout = (int) config('ai_question_generation.text_extraction.pdf.render_timeout_seconds', 60);
        $dpi = (int) config('ai_question_generation.text_extraction.pdf.ocr_render_dpi', 220);
        $maxPages = (int) config('ai_question_generation.text_extraction.pdf.ocr_max_pages', 20);
        $outputPrefix = $tempDirectory . DIRECTORY_SEPARATOR . 'page';

        $process = new Process([
            $binary,
            '-r',
            (string) max(120, $dpi),
            '-png',
            '-f',
            '1',
            '-l',
            (string) max(1, $maxPages),
            $filePath,
            $outputPrefix,
        ]);
        $process->setTimeout($timeout);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            throw AiQuestionGenerationException::assetTextExtractionFailed(
                path: $asset->storage_path,
                reason: 'PDF OCR rendering timed out.'
            );
        } catch (Throwable $exception) {
            throw AiQuestionGenerationException::assetTextExtractionFailed(
                path: $asset->storage_path,
                reason: $exception->getMessage()
            );
        }

        if (! $process->isSuccessful()) {
            throw AiQuestionGenerationException::assetTextExtractionFailed(
                path: $asset->storage_path,
                reason: trim($process->getErrorOutput()) ?: 'PDF OCR rendering process failed.'
            );
        }

        $pages = glob($tempDirectory . DIRECTORY_SEPARATOR . 'page-*.png') ?: [];
        natsort($pages);
        $pages = array_values($pages);

        if ($pages === []) {
            throw AiQuestionGenerationException::assetTextExtractionFailed(
                path: $asset->storage_path,
                reason: 'PDF OCR rendering produced no page images.'
            );
        }

        return $pages;
    }

    private function ocrPage(string $pagePath): string
    {
        $binary = (string) config('ai_question_generation.text_extraction.ocr.binary', 'tesseract');
        $timeout = (int) config('ai_question_generation.text_extraction.ocr.timeout_seconds', 45);
        $languages = (string) config('ai_question_generation.text_extraction.ocr.languages', 'ara+eng');
        $pageSegmentationMode = (string) config('ai_question_generation.text_extraction.ocr.page_segmentation_mode', 6);

        $process = new Process([
            $binary,
            $pagePath,
            'stdout',
            '-l',
            $languages,
            '--psm',
            $pageSegmentationMode,
        ]);
        $process->setTimeout($timeout);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            return '';
        } catch (Throwable) {
            return '';
        }

        return $process->isSuccessful() ? $process->getOutput() : '';
    }

    /**
     * @return array<int, array{name: string, options: array<int, string>}>
     */
    private function pdftotextStrategies(): array
    {
        return [
            ['name' => 'layout', 'options' => ['-layout']],
            ['name' => 'simple', 'options' => ['-simple']],
            ['name' => 'raw', 'options' => ['-raw']],
        ];
    }

    private function isUsableText(string $text): bool
    {
        return $this->normalizedTextLength($text) >= $this->minimumTextChars();
    }

    private function minimumTextChars(): int
    {
        return (int) config('ai_question_generation.text_extraction.min_extracted_text_chars', 40);
    }

    private function normalizedTextLength(string $text): int
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        return mb_strlen(trim($text));
    }

    /**
     * @return array<string, mixed>
     */
    private function attemptDetails(string $strategy, bool $successful, string $text, ?string $error): array
    {
        return [
            'strategy' => $strategy,
            'successful' => $successful,
            'text_chars' => $this->normalizedTextLength($text),
            'error' => $error,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $attempts
     */
    private function buildInsufficientTextReason(string $pdftotextText, string $ocrText, array $attempts): string
    {
        return sprintf(
            'PDF extraction produced insufficient text. minimum_chars=%d, pdftotext_best_chars=%d, ocr_chars=%d, pdftotext_attempts=%s',
            $this->minimumTextChars(),
            $this->normalizedTextLength($pdftotextText),
            $this->normalizedTextLength($ocrText),
            json_encode($attempts, JSON_UNESCAPED_UNICODE)
        );
    }

    private function makeTempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ai-question-generation-pdf-ocr-' . bin2hex(random_bytes(8));

        if (! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw AiQuestionGenerationException::assetTextExtractionFailed(
                reason: 'Failed to create temporary directory for PDF OCR.'
            );
        }

        return $directory;
    }

    private function deleteDirectory(string $directory): void
    {
        foreach (glob($directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        if (is_dir($directory)) {
            @rmdir($directory);
        }
    }
}
