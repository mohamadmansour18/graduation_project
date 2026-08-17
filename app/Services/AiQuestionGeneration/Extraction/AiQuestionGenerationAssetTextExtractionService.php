<?php

namespace App\Services\AiQuestionGeneration\Extraction;

use App\Exceptions\Api\AiQuestionGenerationException;
use App\Models\AiQuestionGenerationAsset;
use App\Models\AiQuestionGenerationRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AiQuestionGenerationAssetTextExtractionService
{
    public function __construct(
        private readonly PdfTextExtractor $pdfTextExtractor,
        private readonly ImageOcrTextExtractor $imageOcrTextExtractor
    ) {}

    /**
     * @return array<int, ExtractedAssetText>
     * @throws AiQuestionGenerationException
     */
    public function extractAssets(AiQuestionGenerationRequest $generationRequest): array
    {
        $extractedAssets = [];

        foreach ($generationRequest->assets->sortBy('position')->values() as $asset) {
            $extractedAssets[] = $this->extractAsset($asset);
        }

        return $extractedAssets;
    }

    public function extractPromptContext(AiQuestionGenerationRequest $generationRequest): string
    {
        $sections = array_map(
            fn (ExtractedAssetText $assetText): string => $assetText->formattedForPrompt(),
            $this->extractAssets($generationRequest)
        );

        return trim(implode("\n\n---\n\n", $sections));
    }

    public function extractAsset(AiQuestionGenerationAsset $asset): ExtractedAssetText
    {
        $startedAt = hrtime(true);

        Log::info('AI question generation asset text extraction started.', [
            'generation_request_id' => $asset->ai_question_generation_id,
            'asset_id' => $asset->id,
            'asset_position' => $asset->position,
            'mime_type' => $asset->mime_type,
            'size_bytes' => $asset->size_bytes,
        ]);

        try {
            $filePath = $this->getStoredAssetPath($asset);

            $rawText = match (true) {
                $this->isPdfAsset($asset) => $this->pdfTextExtractor->extract($asset, $filePath),
                $this->isImageAsset($asset) => $this->imageOcrTextExtractor->extract($asset, $filePath),
                default => throw AiQuestionGenerationException::providerUnsupportedSourceType(
                    provider: 'TextExtraction',
                    sourceType: $asset->mime_type
                ),
            };

            $text = $this->prepareExtractedText($asset, $rawText);

            Log::info('AI question generation asset text extraction completed.', [
                'generation_request_id' => $asset->ai_question_generation_id,
                'asset_id' => $asset->id,
                'extracted_characters_count' => mb_strlen($text),
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            ]);

            return new ExtractedAssetText(
                assetId: $asset->id,
                originalName: (string) $asset->original_name,
                mimeType: (string) $asset->mime_type,
                text: $text,
            );
        } catch (Throwable $exception) {
            Log::channel('errors')->error('AI question generation asset text extraction failed.', [
                'generation_request_id' => $asset->ai_question_generation_id,
                'asset_id' => $asset->id,
                'mime_type' => $asset->mime_type,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            ]);

            throw $exception;
        }
    }

    public function prepareExtractedText(AiQuestionGenerationAsset $asset, string $rawText): string
    {
        $text = $this->normalizeExtractedText($rawText);

        $this->assertExtractedTextIsUsable($asset, $text);

        return $this->limitExtractedText($text);
    }

    public function normalizeExtractedText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function assertExtractedTextIsUsable(AiQuestionGenerationAsset $asset, string $text): void
    {
        $minChars = (int) config('ai_question_generation.text_extraction.min_extracted_text_chars', 40);

        if (mb_strlen($text) < $minChars) {
            throw AiQuestionGenerationException::assetTextExtractionFailed(
                path: $asset->storage_path,
                reason: 'Extracted text is shorter than the configured minimum.'
            );
        }
    }

    private function limitExtractedText(string $text): string
    {
        $maxChars = (int) config('ai_question_generation.text_extraction.max_extracted_text_chars', 60000);

        if ($maxChars <= 0 || mb_strlen($text) <= $maxChars) {
            return $text;
        }

        return mb_substr($text, 0, $maxChars);
    }

    private function getStoredAssetPath(AiQuestionGenerationAsset $asset): string
    {
        $filePath = Storage::disk($asset->storage_disk)->path($asset->storage_path);

        if (! is_file($filePath)) {
            throw AiQuestionGenerationException::temporaryFileMissing(
                path: $asset->storage_path
            );
        }

        return $filePath;
    }

    private function isImageAsset(AiQuestionGenerationAsset $asset): bool
    {
        return str_starts_with((string) $asset->mime_type, 'image/');
    }

    private function isPdfAsset(AiQuestionGenerationAsset $asset): bool
    {
        return $asset->mime_type === 'application/pdf';
    }

    private function elapsedMilliseconds(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
    }
}
