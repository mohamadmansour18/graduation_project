<?php

namespace App\Http\Controllers\V1\Library;

use App\Exceptions\Api\LibraryMaterialException;
use App\Services\LibraryMaterial\LibraryMaterialDownloadService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LibraryMaterialDownloadController
{
    use ApiResponse;

    public function __construct(
        private readonly LibraryMaterialDownloadService $service
    ) {}

    /**
     * @throws LibraryMaterialException
     */
    public function download(int $libraryMaterial): BinaryFileResponse|JsonResponse
    {
        $result = $this->service->download(
            userId: Auth::id(),
            materialId: $libraryMaterial
        );

        if (($result['delete_after_send'] ?? false) === true) {
            return $this->downloadAndDeleteResponse(
                filePath: $result['file_path'],
                downloadName: $result['download_name'],
                headers: $result['headers']
            );
        }

        return $this->downloadResponse(
            filePath: $result['file_path'],
            downloadName: $result['download_name'],
            headers: $result['headers']
        );
    }
}
