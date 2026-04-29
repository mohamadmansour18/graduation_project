<?php

namespace App\Helpers;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class DateProcessor
{
    public static function fromTimestamp(int|string|null $timestamp): ?string
    {
        if (! $timestamp) {
            return null;
        }

        $date = is_numeric($timestamp)
            ? Carbon::createFromTimestamp((int) $timestamp)
            : Carbon::parse($timestamp);

        if ($date->diffInDays(now()) > 7) {
            return $date->Format('d F Y');
        }

        return $date->diffForHumans([
            'syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW,
            'short' => false,
            'parts' => 1,
        ]);
    }
}
