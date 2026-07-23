<?php

namespace App\Services\LibraryMaterial;

use App\DTOs\Notifications\NotificationPayload;
use App\Helpers\ImageProcessor;
use App\Repositories\Library\LibraryMaterialReportRepository;
use App\Services\Notifications\NotificationCenter;
use App\Support\LibraryMaterialReportThresholdPolicy;

class LibraryMaterialReportService
{
    public function __construct(
        private readonly LibraryMaterialReportRepository $repository,
        private readonly LibraryMaterialReportThresholdPolicy $thresholdPolicy,
        private readonly NotificationCenter $notificationCenter,
    ) {}

    public function report(int $userId, int $materialId, string $reason, ?string $description): array
    {
        $result = $this->repository->createReportAndMaybeMarkAsReported(
            userId: $userId,
            materialId: $materialId,
            reason: $reason,
            description: $description,
            thresholdPolicy: $this->thresholdPolicy
        );

        if (
            ($result['statusChangedToReported'] ?? false) === true
            && ! empty($result['notificationData'])
        ) {
            $this->sendMaterialMarkedAsReportedNotifications($result['notificationData']);
        }

        return $result;
    }

    private function sendMaterialMarkedAsReportedNotifications(array $data): void
    {

        $ownerPayload = NotificationPayload::make(
            title: 'تم الإبلاغ عن محتواك',
            body: "تم تحويل محتواك \"{$data['material_title']}\" إلى حالة مُبلّغ عنه بسبب وصول البلاغات إلى الحد المطلوب.",
            metadata: [
                'type' => 'library_material_marked_as_reported',
                'category' => 'report',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#FFE7E7',
                    'icon' => ImageProcessor::urlOrDefault('system-notification/flag.svg' , 'defaults/notification.svg' , 'public'),
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
            payload: $ownerPayload,
        );

        $reviewerIds = $this->repository->getDashboardContentReviewerUserIds();

        if (empty($reviewerIds)) {
            return;
        }

        $dashboardPayload = NotificationPayload::make(
            title: 'محتوى تم تحويله إلى مُبلّغ عنه',
            body: "تم تحويل محتوى بعنوان \"{$data['material_title']}\" إلى حالة مُبلّغ عنه.",
            metadata: [
                'type' => 'dashboard_library_material_marked_as_reported',
                'category' => 'library_review',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#FFE7E7',
                    'icon' => ImageProcessor::urlOrDefault('system-notification/flag.svg' , 'defaults/notification.svg' , 'public'),
                ],

                'actor' => null,

                'navigation' => [
                    'screen' => 'library_material_details',
                    'action' => 'open',
                ],

                'params' => [
                    'material_id' => (int) $data['material_id'],
                ],

            ],
        );

        $this->notificationCenter->sendToWeb(
            userIds: $reviewerIds,
            payload: $dashboardPayload,
        );
    }
}
