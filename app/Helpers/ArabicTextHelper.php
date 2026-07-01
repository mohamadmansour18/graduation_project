<?php

namespace App\Helpers;

class ArabicTextHelper
{
    /**
     * تصحيح عرض النصوص العربية عند وجود كلمات إنكليزية أو أرقام داخل الجملة.
     */
    public static function fixBidi(?string $text): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        $rightToLeftMark = "\u{200F}";

        return $rightToLeftMark . $text . $rightToLeftMark;
    }

    public static function clean(?string $text): string
    {
        return str_replace(
            ["\u{200E}", "\u{200F}", "\u{061C}"],
            '',
            (string) $text
        );
    }
}
