<?php

namespace App\Services\AiQuestionGeneration\Validation;

use App\Exceptions\Api\AiQuestionGenerationException;
use GdImage;
use Illuminate\Http\UploadedFile;

class ImageContentHeuristicValidator
{
    /*
    1. يأخذ الصورة المرفوعة.
    2. يحصل على مسارها المؤقت.
    3. يحاول قراءة معلوماتها.
    4. إذا ليست صورة صالحة يرمي خطأ.
    5. يستخرج العرض والارتفاع والنوع.
    6. يفحص الأبعاد.
    7. يحول الصورة إلى GdImage قابلة للتحليل.
    8. يصنع نسخة صغيرة منها.
    9. يحسب تباين وسطوع الصورة.
    10. ينظف الذاكرة.
    11. إذا الصورة فارغة أو موحدة يرمي خطأ.
    12. إذا لم يحدث أي خطأ، يعني الصورة صالحة.
    */

    /**
     * @throws AiQuestionGenerationException
     */
    public function validate(UploadedFile $file, int $fileIndex): void
    {
        $imagePath = $file->getRealPath();
        $imageInfo = $imagePath ? @getimagesize($imagePath) : false ;

        if ($imageInfo === false) {
            throw AiQuestionGenerationException::imageCannotBeProcessed($fileIndex);
        }

        [$width, $height, $imageType] = $imageInfo;

        $this->validateDimensions($width, $height, $fileIndex);

        $sourceImage = $this->createImageResource($imagePath, $imageType);

        if (! $sourceImage) {
            throw AiQuestionGenerationException::imageCannotBeProcessed($fileIndex);
        }

        try {
            $sampleImage = $this->createSampleImage($sourceImage, $width, $height);

            if (! $sampleImage) {
                throw AiQuestionGenerationException::imageCannotBeProcessed($fileIndex);
            }

            $stats = $this->calculateBrightnessStats($sampleImage);
        } finally {
            imagedestroy($sourceImage);

            if (isset($sampleImage) && $sampleImage instanceof GdImage) {
                imagedestroy($sampleImage);
            }
        }

        if ($this->isBlankOrUniform($stats)) {
            throw AiQuestionGenerationException::imageIsBlankOrUniform($fileIndex);
        }
    }

    private function validateDimensions(int $width, int $height, int $fileIndex): void
    {
        $minWidth = (int) config('ai_question_generation.local_validation.min_image_width', 80);
        $minHeight = (int) config('ai_question_generation.local_validation.min_image_height', 80);
        $maxPixels = (int) config('ai_question_generation.local_validation.max_image_pixels', 24_000_000);

        if ($width < $minWidth || $height < $minHeight) {
            throw AiQuestionGenerationException::imageTooSmall($fileIndex, $minWidth, $minHeight);
        }

        if (($width * $height) > $maxPixels) {
            throw AiQuestionGenerationException::imageTooLargeToProcess($fileIndex);
        }
    }

    private function createImageResource(string $imagePath, int $imageType): ?GdImage
    {
        //check if the GD library is active or not
        if (! extension_loaded('gd')) {
            return null;
        }

        return match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($imagePath) ?: null,
            IMAGETYPE_PNG => @imagecreatefrompng($imagePath) ?: null,
            default => null,
        };
    }

    private function createSampleImage(GdImage $sourceImage, int $sourceWidth, int $sourceHeight): ?GdImage
    {
        $sampleSize = (int) config('ai_question_generation.local_validation.image_sample_size', 64);
        $sampleImage = imagecreatetruecolor($sampleSize, $sampleSize);  //create an empty image with specific dimensions

        if (! $sampleImage) {
            return null;
        }

        //copy content of original image to empty image
        $resampled = imagecopyresampled(
            $sampleImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $sampleSize,
            $sampleSize,
            $sourceWidth,
            $sourceHeight
        );

        if (! $resampled) {
            imagedestroy($sampleImage);

            return null;
        }

        return $sampleImage;
    }

    private function calculateBrightnessStats(GdImage $sampleImage): array
    {
        $width = imagesx($sampleImage);
        $height = imagesy($sampleImage);
        $pixelCount = $width * $height;

        $sum = 0.0;             //Total brightness per pixel
        $sumSquares = 0.0;      //the sum of the squares of the brightness is later used to calculate the contrast
        $min = 255.0;           //min brightness in image "start with 255 because the highest value of brightness is 255"
        $max = 0.0;             //max brightness in image "start with 0 because the lowest value of brightness is 0"

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {

                $rgb = imagecolorat($sampleImage, $x, $y);      //get RGB value for specific pixel "the function return color in one int value like : 255150200"

                $red = ($rgb >> 16) & 0xFF;
                $green = ($rgb >> 8) & 0xFF;
                $blue = $rgb & 0xFF;

                $brightness = (0.299 * $red) + (0.587 * $green) + (0.114 * $blue);

                $sum += $brightness;
                $sumSquares += $brightness * $brightness;
                $min = min($min, $brightness);
                $max = max($max, $brightness);
            }
        }

        $mean = $sum / $pixelCount;
        $variance = max(0, ($sumSquares / $pixelCount) - ($mean * $mean));

        return [
            'mean' => $mean,                //avg brightness
            'stddev' => sqrt($variance),    //الانحراف المعياري يخبرنا هل الصورة موحدة أم فيها اختلافات
            'range' => $max - $min,         //المدى بين افتح بكسل واغمق بكسل
        ];
    }

    private function isBlankOrUniform(array $stats): bool
    {
        $lowBrightness = (float) config('ai_question_generation.local_validation.blank_brightness_low', 8);
        $highBrightness = (float) config('ai_question_generation.local_validation.blank_brightness_high', 247);
        $stddevThreshold = (float) config('ai_question_generation.local_validation.blank_stddev_threshold', 2.5);
        $rangeThreshold = (float) config('ai_question_generation.local_validation.blank_range_threshold', 8);

        $isNearlyUniform = $stats['stddev'] <= $stddevThreshold && $stats['range'] <= $rangeThreshold;

        if (! $isNearlyUniform) {
            return false;
        }

        return $stats['mean'] <= $lowBrightness
            || $stats['mean'] >= $highBrightness
            || $stats['range'] <= $rangeThreshold;
    }
}
