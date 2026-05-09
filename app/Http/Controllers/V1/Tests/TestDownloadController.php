<?php

namespace App\Http\Controllers\V1\Tests;

use App\Services\Tests\TestDownloadService;
use App\Trait\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TestDownloadController
{
    use ApiResponse;

    public function __construct(
        private readonly TestDownloadService $testDownloadService
    ) {}

    public function downloadPdf(int $testId): BinaryFileResponse
    {
        $result = $this->testDownloadService->downloadPdf(
            testId: $testId,
            userId: Auth::id()
        );

        return $this->downloadResponse(
            filePath: $result['file_path'],
            downloadName: $result['download_name'],
            headers: [
                'Content-Type' => 'application/pdf',
            ]
        );
    }
}
