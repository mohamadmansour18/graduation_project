<?php

namespace App\Services\LibraryMaterial;

use App\DTOs\Notifications\NotificationPayload;
use App\Enums\LibraryMaterialReviewStatus;
use App\Events\LibraryMaterialPublishedDeleted;
use App\Helpers\ImageProcessor;
use App\Models\LibraryMaterial;
use App\Repositories\Library\StaleLibraryMaterialReviewCleanupRepository;
use App\Services\Notifications\NotificationCenter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class StaleLibraryMaterialReviewCleanupService
{
    public function __construct(
        private readonly StaleLibraryMaterialReviewCleanupRepository $repository,
        private readonly NotificationCenter $notificationCenter,
    ) {
    }

    public function handle(int $olderThanHours = 48, int $limit = 200): array
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $cutoff = $now->subHours($olderThanHours);

        $candidateIds = $this->repository->staleCandidateIds(
            cutoff: $cutoff,
            limit: $limit,
        );

        $summary = [
            'checked' => count($candidateIds),
            'processed' => 0,
            'force_deleted' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($candidateIds as $materialId) {
            try {
                $result = $this->processOne(
                    materialId: $materialId,
                    cutoff: $cutoff,
                    now: $now,
                );

                if (! $result) {
                    $summary['skipped']++;
                    continue;
                }

                $summary['processed']++;
                $summary['force_deleted']++;

                $this->deleteMaterialFiles($result['files']);
                $this->dispatchEvents($result);
                $this->sendOwnerNotification($result);

                Log::channel('audit')->info('stale_library_material_review_status_cleaned', [
                    'library_material_id' => $result['material_id'],
                    'creator_user_id' => $result['creator_user_id'],
                    'from_status' => $result['from_status'],
                    'deletion_type' => 'force_delete',
                    'status_changed_at' => $result['status_changed_at'],
                ]);
            } catch (Throwable $exception) {
                $summary['failed']++;

                Log::channel('errors')->error('Failed to clean stale library material review status', [
                    'library_material_id' => $materialId,
                    'message' => $exception->getMessage(),
                    'exception_class' => $exception::class,
                ]);
            }
        }

        return $summary;
    }

    private function processOne(int $materialId, CarbonImmutable $cutoff, CarbonImmutable $now): ?array
    {
        return DB::transaction(function () use ($materialId, $cutoff, $now) {
            $material = $this->repository->findCandidateForUpdate(
                materialId: $materialId,
                cutoff: $cutoff,
            );

            if (! $material) {
                return null;
            }

            $fromStatus = $this->normalizeStatus($material->review_status);

            $files = $material->libraryMaterialAssets
                ->map(fn ($asset) => [
                    'disk' => $asset->storage_disk,
                    'path' => $asset->storage_path,
                ])
                ->filter(fn ($file) => filled($file['disk']) && filled($file['path']))
                ->values()
                ->all();

            $publishedAt = $material->published_at
                ? CarbonImmutable::parse($material->published_at, config('app.timezone'))
                : null;

            $this->repository->forceDelete($material);

            return [
                'material_id' => (int) $material->id,
                'material_title' => (string) $material->title,
                'creator_user_id' => (int) $material->creator_user_id,
                'from_status' => $fromStatus->value,
                'deletion_reason' => $this->deletionReason($fromStatus),
                'deleted_at' => $now->toDateTimeString(),
                'status_changed_at' => $material->current_status_changed_at,
                'was_published' => $fromStatus === LibraryMaterialReviewStatus::Reported && $publishedAt !== null,
                'published_at' => $publishedAt,
                'files' => $files,
            ];
        });
    }

    private function normalizeStatus(mixed $status): LibraryMaterialReviewStatus
    {
        return $status instanceof LibraryMaterialReviewStatus
            ? $status
            : LibraryMaterialReviewStatus::from($status);
    }

    private function deletionReason(LibraryMaterialReviewStatus $status): string
    {
        return match ($status) {
            LibraryMaterialReviewStatus::New => 'تم حذف المحتوى تلقائيًا لأنه بقي في حالة مسودة لأكثر من 48 ساعة.',
            LibraryMaterialReviewStatus::Reported => 'تم حذف المحتوى تلقائيًا لأنه بقي في حالة مبلغ عنه لأكثر من 48 ساعة.',
            default => 'تم حذف المحتوى تلقائيًا بسبب بقاء حالته معلقة لأكثر من 48 ساعة.',
        };
    }

    private function deleteMaterialFiles(array $files): void
    {
        foreach ($files as $file) {
            try {
                Storage::disk($file['disk'])->delete($file['path']);
            } catch (Throwable $exception) {
                Log::warning('Failed to delete stale library material file.', [
                    'disk' => $file['disk'],
                    'path' => $file['path'],
                    'message' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function dispatchEvents(array $result): void
    {
        if (! $result['was_published'] || ! $result['published_at']) {
            return;
        }

        LibraryMaterialPublishedDeleted::dispatch(
            $result['material_id'],
            $result['published_at'],
        );
    }

    private function sendOwnerNotification(array $data): void
    {
        $materialTitle = $data['material_title'] ?: 'محتواك';

        $payload = NotificationPayload::make(
            title: 'تم حذف محتواك تلقائيًا',
            body: "تم حذف محتواك: {$materialTitle}. السبب: {$data['deletion_reason']}",
            metadata: [
                'type' => 'stale_library_material_review_status_deleted',
                'category' => 'library_review',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#FFE7E7',
                    'icon' => ImageProcessor::urlOrDefault('system-notification/trash.svg', 'defaults/notification.svg', 'public'),
                ],

                'actor' => null,

                'navigation' => [
                    'screen' => 'my_library_materials',
                    'action' => 'open',
                ],

                'params' => [
                    'material_id' => (int) $data['material_id'],
                    'delete_type' => 'force_delete',
                ],
            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $data['creator_user_id'],
            payload: $payload,
        );
    }
}
