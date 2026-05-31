<?php

namespace App\Services\Tests;

class ScientificChangeDetector
{
    public function isTitleSignificant(?string $old, ?string $new): bool
    {
        return $this->similarityPercent($old, $new) < 90;
    }

    public function isDescriptionSignificant(?string $old, ?string $new): bool
    {
        if ($this->similarityPercent($old, $new) < 85) {
            return true;
        }

        return $this->wordCountChangePercent($old, $new) > 20;
    }

    public function isQuestionTextSignificant(?string $old, ?string $new): bool
    {
        return $this->similarityPercent($old, $new) < 90;
    }

    public function isHintSignificant(?string $old, ?string $new): bool
    {
        return $this->similarityPercent($old, $new) < 80;
    }

    public function isAnswerTextSignificant(?string $old, ?string $new): bool
    {
        return $this->similarityPercent($old, $new) < 90;
    }

    public function normalize(?string $text): string
    {
        $text = trim((string) $text);

        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        $text = str_replace(['أ', 'إ', 'آ'], 'ا', $text);

        $text = preg_replace('/[\x{064B}-\x{065F}]/u', '', $text) ?? $text;

        return mb_strtolower($text);
    }

    private function similarityPercent(?string $old, ?string $new): float
    {
        $old = $this->normalize($old);
        $new = $this->normalize($new);

        if ($old === $new) {
            return 100;
        }

        if ($old === '' || $new === '') {
            return 0;
        }

        similar_text($old, $new, $percent);

        return (float) $percent;
    }

    private function wordCountChangePercent(?string $old, ?string $new): float
    {
        $oldWords = preg_split('/\s+/u', $this->normalize($old), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $newWords = preg_split('/\s+/u', $this->normalize($new), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $oldCount = count($oldWords);
        $newCount = count($newWords);

        if ($oldCount === 0 && $newCount === 0) {
            return 0;
        }

        if ($oldCount === 0) {
            return 100;
        }

        return abs($newCount - $oldCount) / $oldCount * 100;
    }
}
