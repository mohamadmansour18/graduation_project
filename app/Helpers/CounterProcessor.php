<?php

namespace App\Helpers;

class CounterProcessor
{
    public static function compact(int|string|null $number): string
    {
        if ($number === null || $number === '') {
            return '0';
        }

        $number = (int) $number;

        if ($number >= 1_000_000) {
            return self::formatCompactNumber($number / 1_000_000) . 'M';
        }

        if ($number >= 1_000) {
            return self::formatCompactNumber($number / 1_000) . 'K';
        }

        return (string) $number;
    }

    private static function formatCompactNumber(float $value): string
    {
        $rounded = round($value, 1);

        return str_ends_with((string) $rounded, '.0')
            ? (string) (int) $rounded
            : (string) $rounded;
    }
}
