<?php

namespace App\Helpers;

class ArabicSearchNormalizer
{
    public static function normalize(string $value): string
    {
        $value = trim($value);

        // إزالة التشكيل
        $value = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $value);

        // توحيد أشكال الألف
        $value = str_replace(['أ', 'إ', 'آ'], 'ا', $value);

        // توحيد الياء والألف المقصورة
        $value = str_replace('ى', 'ي', $value);

        // توحيد التاء المربوطة حسب الحاجة
        $value = str_replace('ة', 'ه', $value);

        // توحيد المسافات
        $value = preg_replace('/\s+/u', ' ', $value);

        return $value;
    }
}
