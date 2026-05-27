<?php

namespace App\Services\AiQuestionGeneration\Validation;

use App\Exceptions\Api\AiQuestionGenerationException;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;
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
            $process = new Process(['pdfinfo', $filePath]);
            $process->setTimeout(10);
            $process->run();

            if (! $process->isSuccessful()) {
                throw AiQuestionGenerationException::invalidPdfFile();
            }

            $output = $process->getOutput();

            if (! preg_match('/Pages:\s+(\d+)/i', $output, $matches)) {
                throw AiQuestionGenerationException::invalidPdfFile();
            }

            $pageCount = (int) $matches[1];

        } catch (Throwable $exception) {

            throw AiQuestionGenerationException::invalidPdfFile();
        }

        if ($pageCount < 1) {
            throw AiQuestionGenerationException::emptyPdfFile();
        }
    }
}
