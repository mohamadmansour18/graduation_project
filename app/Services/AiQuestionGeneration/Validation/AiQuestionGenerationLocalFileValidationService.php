<?php

namespace App\Services\AiQuestionGeneration\Validation;

class AiQuestionGenerationLocalFileValidationService
{
    public function __construct(
        private readonly ImageContentHeuristicValidator $imageValidator,
        private readonly PdfStructureValidator $pdfValidator
    ) {}

    public function validate(string $sourceType, array $files): void
    {
        if ($sourceType === 'Images') {
            foreach (array_values($files) as $index => $file) {
                $this->imageValidator->validate($file, $index + 1);
            }

            return;
        }

        if ($sourceType === 'Pdf' && isset($files[0])) {
            $this->pdfValidator->validate($files[0]);
        }
    }
}
