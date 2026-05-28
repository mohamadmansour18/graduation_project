<?php

namespace App\Services\AiQuestionGeneration\Extraction;

class ExtractedAssetText
{
    public function __construct(
        public readonly int|string|null $assetId,
        public readonly string $originalName,
        public readonly string $mimeType,
        public readonly string $text,
    ) {}

    public function formattedForPrompt(): string
    {
        return trim(<<<TEXT
File: {$this->originalName}
Mime-Type: {$this->mimeType}

{$this->text}
TEXT);
    }
}
