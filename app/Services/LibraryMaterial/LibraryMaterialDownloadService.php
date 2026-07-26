<?php

namespace App\Services\LibraryMaterial;

use App\Enums\LibraryMaterialContentKind;
use App\Exceptions\Api\LibraryMaterialException;
use App\Models\LibraryMaterial;
use App\Repositories\Library\LibraryMaterialDownloadRepository;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class LibraryMaterialDownloadService
{
    public function __construct(
        private readonly LibraryMaterialDownloadRepository $repository
    ) {}

    public function download(int $userId, int $materialId): array
    {
        $material = $this->repository->findDownloadableMaterial($userId, $materialId);

        if (! $material) {
            throw LibraryMaterialException::materialNotAvailableForDownload();
        }

        $this->repository->recordDownloadOnce($userId, $material->id);

        if ($material->content_kind === LibraryMaterialContentKind::File) {

            $asset = $material->libraryMaterialAssets->first();

            if (! $asset) {
                throw LibraryMaterialException::materialFileNotFound();
            }

            $filePath = Storage::disk($asset->storage_disk)->path($asset->storage_path);

            if (! is_file($filePath)) {
                throw LibraryMaterialException::materialFileNotFound();
            }

            return [
                'file_path' => $filePath,
                'download_name' => $asset->original_name ?: 'library-material.pdf',
                'headers' => [
                    'Content-Type' => $asset->mime_type ?: 'application/pdf',
                ],
                'delete_after_send' => false,
            ];
        }

        if ($material->libraryMaterialAssets->count() === 1) {
            $asset = $material->libraryMaterialAssets->first();
            $filePath = Storage::disk($asset->storage_disk)->path($asset->storage_path);

            if (! is_file($filePath)) {
                throw LibraryMaterialException::materialFileNotFound();
            }

            return [
                'file_path' => $filePath,
                'download_name' => $asset->original_name ?: basename($asset->storage_path),
                'headers' => [
                    'Content-Type' => $asset->mime_type ?: 'application/octet-stream',
                ],
                'delete_after_send' => false,
            ];
        }

        return $this->createImagesZip($material);
    }

    private function createImagesZip(LibraryMaterial $material): array
    {
        $zipDirectory = storage_path('app/temp/library-material-downloads');

        if (! is_dir($zipDirectory)) {
            mkdir($zipDirectory, 0755, true);
        }

        $zipPath = $zipDirectory . '/library-material-' . $material->id . '-' . uniqid('', true) . '.zip';

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw LibraryMaterialException::materialFileNotFound();
        }

        foreach ($material->libraryMaterialAssets as $asset) {

            $absolutePath = Storage::disk($asset->storage_disk)->path($asset->storage_path);

            if (! is_file($absolutePath)) {
                continue;
            }

            $nameInZip = $this->safeZipFileName(
                originalName: $asset->original_name ?: basename($asset->storage_path),
                position: (int) $asset->position
            );

            $zip->addFile($absolutePath, $nameInZip);
        }

        $zip->close();

        if (! is_file($zipPath)) {
            throw LibraryMaterialException::materialFileNotFound();
        }

        return [
            'file_path' => $zipPath,
            'download_name' => 'library-material-' . $material->id . '.zip',
            'headers' => [
                'Content-Type' => 'application/zip',
            ],
            'delete_after_send' => true,
        ];
    }

    private function safeZipFileName(string $originalName, int $position): string
    {
        $originalName = str_replace(['/', '\\'], '-', $originalName);

        return str_pad((string) $position, 2, '0', STR_PAD_LEFT) . '-' . $originalName;
    }

}
