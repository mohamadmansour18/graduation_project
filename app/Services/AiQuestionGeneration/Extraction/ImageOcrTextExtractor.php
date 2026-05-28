<?php

namespace App\Services\AiQuestionGeneration\Extraction;

use App\Exceptions\Api\AiQuestionGenerationException;
use App\Models\AiQuestionGenerationAsset;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class ImageOcrTextExtractor
{
    public function extract(AiQuestionGenerationAsset $asset, string $filePath): string
    {
        $binary = (string) config('ai_question_generation.text_extraction.ocr.binary', 'tesseract');
        $timeout = (int) config('ai_question_generation.text_extraction.ocr.timeout_seconds', 45);
        $languages = (string) config('ai_question_generation.text_extraction.ocr.languages', 'ara+eng');
        $pageSegmentationMode = (string) config('ai_question_generation.text_extraction.ocr.page_segmentation_mode', 6);

        $process = new Process([
            $binary,
            $filePath,
            'stdout',
            '-l',
            $languages,
            '--psm',
            $pageSegmentationMode,
        ]);
        $process->setTimeout($timeout);

        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            throw AiQuestionGenerationException::assetTextExtractionFailed(
                path: $asset->storage_path,
                reason: 'Image OCR timed out.'
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
                reason: trim($process->getErrorOutput()) ?: 'Image OCR process failed.'
            );
        }

        return $process->getOutput();
    }
}
