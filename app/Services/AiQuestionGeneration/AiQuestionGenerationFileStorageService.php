<?php

namespace App\Services\AiQuestionGeneration;

use App\Models\AiQuestionGenerationRequest;
use App\Repositories\AiQuestionGeneration\AiQuestionGenerationRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiQuestionGenerationFileStorageService
{
    public function __construct(
        private readonly AiQuestionGenerationRepository $repository
    )
    {}

    public function storeUploadedFiles(AiQuestionGenerationRequest $generationRequest, array $files): void
    {
        $disk = config('ai_question_generation.storage_disk');
        $baseDirectory = $this->getRequestDirectory($generationRequest->id);

        foreach (array_values($files) as $index => $file) {
            /** @var UploadedFile $file */

            $extension = $file->getClientOriginalExtension();

            $fileName = Str::uuid()->toString() . '.' . $extension;

            $storagePath = $file->storeAs(
                $baseDirectory,
                $fileName,
                $disk
            );

            $this->repository->createAsset($generationRequest, [
                'storage_disk' => $disk,
                'storage_path' => $storagePath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'sha256_hash' => hash_file('sha256', $file->getRealPath()),
                'position' => $index + 1,
            ]);
        }
    }

    /**
     * يحذف الملفات من storage بعد اكتمال العملية بنجاح.
     * لا يحذف سجلات قاعدة البيانات، فقط الملفات الحقيقية.
     */
    public function deleteStoredAssets(AiQuestionGenerationRequest $generationRequest): void
    {
        $disk = config('ai_question_generation.storage_disk');

        foreach ($generationRequest->assets as $asset) {
            if ($asset->deleted_from_storage_at !== null) {
                continue;
            }

            try {
                Storage::disk($asset->storage_disk)->delete($asset->storage_path);

                $this->repository->markAssetAsDeletedFromStorage($asset->id);
            } catch (\Throwable $exception) {
                Log::channel('errors')->error('Failed to delete AI generation temporary asset.', [
                    'generation_request_id' => $generationRequest->id,
                    'asset_id' => $asset->id,
                    'storage_disk' => $asset->storage_disk,
                    'storage_path' => $asset->storage_path,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        try {
            Storage::disk($disk)->deleteDirectory(
                $this->getRequestDirectory($generationRequest->id)
            );
        } catch (\Throwable $exception) {
            Log::channel('errors')->error('Failed to delete AI generation temporary directory.', [
                'generation_request_id' => $generationRequest->id,
                'storage_disk' => $disk,
                'directory' => $this->getRequestDirectory($generationRequest->id),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * يستخدم عند فشل تخزين الملفات أو فشل إنشاء الطلب قبل إطلاق الـ Job.
     */
    public function deleteRequestDirectory(int $generationRequestId): void
    {
        $disk = config('ai_question_generation.storage_disk');

        Storage::disk($disk)->deleteDirectory(
            $this->getRequestDirectory($generationRequestId)
        );
    }

    function getRequestDirectory(int $generationRequestId): string
    {
        return trim(config('ai_question_generation.temporary_directory'), '/')
            . '/'
            . $generationRequestId;
    }

}

