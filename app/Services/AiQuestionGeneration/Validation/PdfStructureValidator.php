<?php

namespace App\Services\AiQuestionGeneration\Validation;

use App\Exceptions\Api\AiQuestionGenerationException;
use Illuminate\Http\UploadedFile;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReader;
use Throwable;

class PdfStructureValidator
{
    public function validate(UploadedFile $file): void
    {
        $filePath = $file->getRealPath();

        if (! $filePath || ! is_file($filePath)) {
            throw AiQuestionGenerationException::invalidPdfFile();
        }

        try {
            $reader = new PdfReader(
                new PdfParser(StreamReader::createByFile($filePath))
            );

            $pageCount = $reader->getPageCount();
        } catch (Throwable) {
            throw AiQuestionGenerationException::invalidPdfFile();
        }

        if ($pageCount < 1) {
            throw AiQuestionGenerationException::emptyPdfFile();
        }
    }
}
