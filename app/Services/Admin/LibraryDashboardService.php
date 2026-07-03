<?php

namespace App\Services\Admin;

use App\DTOs\Notifications\NotificationPayload;
use App\Enums\LibraryMaterialReviewStatus;
use App\Events\LibraryMaterialFirstApproved;
use App\Events\LibraryMaterialPublishedDeleted;
use App\Exceptions\Api\LibraryMaterialException;
use App\Helpers\BuildActor;
use App\Helpers\ImageProcessor;
use App\Http\Resources\LibraryMaterialListResource;
use App\Models\LibraryMaterial;
use App\Models\User;
use App\Repositories\Admin\LibraryDashboardRepository;
use App\Repositories\Library\LibraryMaterialRepository;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Support\Facades\DB;
use Log;

class LibraryDashboardService
{
    public function __construct(
        private readonly LibraryDashboardRepository $repository,
        private readonly LibraryMaterialRepository $materialRepository,
        private readonly NotificationCenter $notificationCenter,
    )
    {}

    /////////////////////////////////////////////////////////////
    public function listApprovedMaterials(array $filters): array
    {
        $perPage = min((int) ($filters['per_page'] ?? 20), 50);
        $sortBy = $filters['sort_by'] ?? 'latest';

        $materialsPaginator = $this->repository->paginateApprovedMaterials(
            sortBy: $sortBy,
            perPage: $perPage
        );

        return [
            'statistics' => $this->repository->getCurrentYearStatistics(),
            'materials' => LibraryMaterialListResource::collection($materialsPaginator->items()),
            'meta' => [
                'per_page' => $materialsPaginator->perPage(),
                'next_cursor' => optional($materialsPaginator->nextCursor())->encode(),
                'previous_cursor' => optional($materialsPaginator->previousCursor())->encode(),
                'has_more_pages' => $materialsPaginator->hasMorePages(),
            ],
        ];
    }

    /////////////////////////////////////////////////////////////
    public function searchLibraryMaterials(int $userId, string $query, string $mode = 'all_public', int $perPage = 20): array
    {
        $materialsPaginator = $this->materialRepository->searchMaterials(
            userId: $userId,
            query: $query,
            mode: $mode,
            perPage: $perPage
        );

        return [
            'query' => $query,
            'mode' => $mode,
            'materials' => LibraryMaterialListResource::collection($materialsPaginator),
            'meta' => [
                'per_page' => $materialsPaginator->perPage(),
                'next_cursor' => optional($materialsPaginator->nextCursor())->encode(),
                'previous_cursor' => optional($materialsPaginator->previousCursor())->encode(),
                'has_more_pages' => $materialsPaginator->hasMorePages(),
            ],
        ];
    }

    /////////////////////////////////////////////////////////////

    public function showMaterialDetails(int $libraryMaterialId): LibraryMaterial
    {
        return $this->repository->findMaterialDetailsOrFail($libraryMaterialId);
    }

    /////////////////////////////////////////////////////////////
    public function getCurrentVersionReports(int $libraryMaterialId, array $filters): array
    {
        $material = $this->repository->findMaterialForReportsOrFail($libraryMaterialId);

        $approvalVersion = (int) $material->current_approval_version;
        $perPage = min((int) ($filters['per_page'] ?? 20), 50);

        $reportsPaginator = $this->repository->paginateReportsForApprovalVersion(
            libraryMaterialId: $material->id,
            approvalVersion: $approvalVersion,
            perPage: $perPage
        );

        return [
            'material' => $material,
            'approval_version' => $approvalVersion,
            'reason_counters' => $this->repository->getReasonCountersForApprovalVersion(
                libraryMaterialId: $material->id,
                approvalVersion: $approvalVersion
            ),
            'reports_paginator' => $reportsPaginator,
        ];
    }

    /////////////////////////////////////////////////////////////

    public function getStatusHistory(int $libraryMaterialId): array
    {
        $material = $this->repository->findMaterialOrFail($libraryMaterialId);

        return [
            'material' => $material,
            'histories' => $this->repository->getHistories($libraryMaterialId),
        ];
    }

    /////////////////////////////////////////////////////////////

    public function approve(User $user, int $libraryMaterialId): void
    {
        $eventPayload = null;
        $notificationPayload = null;

        DB::transaction(function () use ($user, $libraryMaterialId, &$eventPayload , &$notificationPayload)
        {
            $material = $this->repository->findMaterialForUpdateOrFail($libraryMaterialId);

            $fromStatus = $material->review_status->value;

            if ($fromStatus === LibraryMaterialReviewStatus::Approved->value) {
                throw LibraryMaterialException::alreadyApproved();
            }

            if ($fromStatus === LibraryMaterialReviewStatus::Deleted->value) {
                throw LibraryMaterialException::deletedMaterialCannotBeApproved();
            }

            if (! in_array($fromStatus, [LibraryMaterialReviewStatus::New->value , LibraryMaterialReviewStatus::Reported->value], true)) {
                throw LibraryMaterialException::invalidStatusForApproval();
            }

            $openRound = $this->repository->findOpenReviewRoundForUpdate($material->id);

            if (! $openRound) {
                throw LibraryMaterialException::noOpenReviewRound();
            }

            $now = now();

            $isFirstApproval = $fromStatus === LibraryMaterialReviewStatus::New->value;

            $nextApprovalVersion = $isFirstApproval
                ? 1
                : (int) $material->current_approval_version;

            $publishedAt = $isFirstApproval
                ? $now
                : $material->published_at;

            $this->repository->approveOpenRound(
                round: $openRound,
                reviewerUserId: $user->id,
                decidedAt: $now
            );

            $this->repository->approveMaterial(
                material: $material,
                approvalVersion: $nextApprovalVersion,
                publishedAt: $publishedAt
            );

            $this->repository->createStatusHistory(
                libraryMaterialId: $material->id,
                fromStatus: $fromStatus,
                toStatus: LibraryMaterialReviewStatus::Approved->value,
                changedByUserId: $user->id,
                note: $isFirstApproval
                    ? 'تمت الموافقة على نشر المحتوى لأول مرة'
                    : 'تمت الموافقة على المحتوى بعد مراجعة البلاغات'
            );

            Log::channel('audit')->info('library_material_approved', [
                'library_material_id' => $material->id,
                'approved_by_user_id' => $user->id,
                'from_status' => $fromStatus,
                'to_status' => LibraryMaterialReviewStatus::Approved->value,
                'approval_version' => $nextApprovalVersion,
                'is_first_approval' => $isFirstApproval,
            ]);

            $notificationPayload = [
                'material_id' => (int) $material->id,
                'material_title' => $material->title,
                'creator_user_id' => (int) $material->creator_user_id,
                'reviewer_user_id' => (int) $user->id,
                'from_status' => $fromStatus,
                'to_status' => LibraryMaterialReviewStatus::Approved->value,
                'approval_version' => (int) $nextApprovalVersion,
                'published_at' => $publishedAt?->toDateTimeString(),
                'is_first_approval' => $isFirstApproval,
            ];

            if ($isFirstApproval) {
                $eventPayload = [
                    'library_material_id' => $material->id,
                    'published_at' => $publishedAt,
                ];
            }
        });

        if ($eventPayload !== null) {
            LibraryMaterialFirstApproved::dispatch(
                $eventPayload['library_material_id'],
                $eventPayload['published_at']
            );
        }

        if ($notificationPayload !== null) {
            $this->sendMaterialApprovedOwnerNotification($notificationPayload);

            if ($notificationPayload['is_first_approval'] === true) {
                $this->sendMaterialPublishedToFollowersNotification($notificationPayload);
            }
        }
    }

    /////////////////////////////////////////////////////////////

    public function delete(User $user, int $libraryMaterialId , string $deleteReason): void
    {
        $eventPayload = null;
        $notificationPayload = null;

        DB::transaction(function () use ($user, $libraryMaterialId, &$eventPayload , &$notificationPayload , $deleteReason) {
            $material = $this->repository->findMaterialForUpdateOrFail($libraryMaterialId);

            $fromStatus = $material->review_status->value;

            if ($fromStatus === LibraryMaterialReviewStatus::Deleted->value) {
                throw LibraryMaterialException::alreadyDeleted();
            }

            $shouldDecrementSummary = in_array($fromStatus ,[LibraryMaterialReviewStatus::Approved->value , LibraryMaterialReviewStatus::Reported->value] , true)
                && $material->published_at !== null;

            if ($shouldDecrementSummary) {
                $eventPayload = [
                    'library_material_id' => $material->id,
                    'published_at' => $material->published_at,
                ];
            }

            $notificationPayload = [
                'material_id' => (int) $material->id,
                'material_title' => $material->title,
                'creator_user_id' => (int) $material->creator_user_id,
                'deleted_by_user_id' => (int) $user->id,
                'from_status' => $fromStatus,
                'delete_reason' => $deleteReason,
                'deleted_at' => now()->toDateTimeString(),
                'was_published' => $shouldDecrementSummary,
                'published_at' => optional($material->published_at)->toDateTimeString(),
            ];

            Log::channel('audit')->info('library_material_force_deleted', [
                'library_material_id' => $material->id,
                'deleted_by_user_id' => $user->id,
                'from_status' => $fromStatus,
                'was_published' => $shouldDecrementSummary,
                'published_at' => optional($material->published_at)->toDateTimeString(),
            ]);

            $this->repository->forceDeleteMaterial($material);
        });

        if ($eventPayload !== null) {
            LibraryMaterialPublishedDeleted::dispatch(
                $eventPayload['library_material_id'],
                $eventPayload['published_at']
            );
        }

        if ($notificationPayload !== null) {
            $this->sendLibraryMaterialDeletedNotification($notificationPayload);
        }
    }

    private function sendMaterialApprovedOwnerNotification(array $data): void
    {
        $materialTitle = $data['material_title'] ?? 'محتواك';

        $isFirstApproval = (bool) $data['is_first_approval'];

        $title = $isFirstApproval
            ? 'تمت الموافقة على نشر محتواك'
            : 'تمت إعادة الموافقة على محتواك';

        $body = $isFirstApproval
            ? "تمت الموافقة على نشر محتواك: {$materialTitle}"
            : "تمت إعادة الموافقة على محتواك: {$materialTitle} بعد مراجعة البلاغات.";

        $payload = NotificationPayload::make(
            title: $title,
            body: $body,
            metadata: [
                'type' => $isFirstApproval
                    ? 'library_material_approved'
                    : 'library_material_reapproved_after_report',

                'category' => 'library_review',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#E4FFE5',
                    'icon' => ImageProcessor::urlOrDefault('system-notification/true.svg' , 'defaults/notification.svg' , 'public'),
                ],

                'actor' => null,

                'navigation' => [
                    'screen' => 'my_library_material_details',
                    'action' => 'open',
                ],

                'params' => [
                    'material_id' => (int) $data['material_id'],
                ],
            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $data['creator_user_id'],
            payload: $payload,
        );
    }

    private function sendMaterialPublishedToFollowersNotification(array $data): void
    {
        $followerIds = $this->repository->getFollowerUserIds((int) $data['creator_user_id']);

        if (empty($followerIds)) {
            return;
        }

        $materialTitle = $data['material_title'] ?? 'محتوى جديد';

        $payload = NotificationPayload::make(
            title: 'نشر محتوى',
            body: "قام بنشر محتوى جديد بعنوان: {$materialTitle}",
            metadata: [
                'type' => 'followed_user_published_library_material',
                'category' => 'social',

                'presentation' => [
                    'mode' => 'user',
                    'floor_color' => null,
                    'icon' => null,
                ],

                'actor' => BuildActor::buildUserActor((int) $data['creator_user_id']),

                'navigation' => [
                    'screen' => 'library_material_details',
                    'action' => 'open',

                ],

                'params' => [
                    'material_id' => (int) $data['material_id'],
                    'creator_user_id' => (int) $data['creator_user_id'],
                ],
            ],
        );

        $this->notificationCenter->sendToUsers(
            userIds: $followerIds,
            payload: $payload,
        );
    }
    private function sendLibraryMaterialDeletedNotification(array $data): void
    {
        $materialTitle = $data['material_title'] ?? 'محتواك';

        $payload = NotificationPayload::make(
            title: 'تم حذف محتواك',
            body: "تم حذف محتواك: {$materialTitle}. السبب: {$data['delete_reason']}",
            metadata: [
                'type' => 'library_material_deleted_by_dashboard',
                'category' => 'library_review',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#FFE7E7',
                    'icon' => ImageProcessor::urlOrDefault('system-notification/trash.svg' , 'defaults/notification.svg' , 'public'),
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
