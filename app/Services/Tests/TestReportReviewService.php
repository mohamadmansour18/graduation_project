<?php

namespace App\Services\Tests;

use App\DTOs\Notifications\NotificationPayload;
use App\Events\TestReviewStateChanged;
use App\Exceptions\Api\TestException;
use App\Helpers\ImageProcessor;
use App\Repositories\Tests\TestReportReviewRepository;
use App\Services\Notifications\NotificationCenter;
use App\Support\TestReviewReportThresholdPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestReportReviewService
{
    public function __construct(
        private readonly TestReportReviewRepository $repository,
        private readonly TestReviewReportThresholdPolicy $thresholdPolicy,
        private readonly NotificationCenter $notificationCenter,
    ) {}

    public function store(int $reviewId, int $reporterUserId, string $reason, ?string $description): array
    {
        $eventPayload = null;
        $notificationPayload = null;

        $isStatusChanged = DB::transaction(function () use ($reviewId, $reporterUserId, $reason, $description , &$eventPayload , &$notificationPayload) {
            $lockedReview = $this->repository->lockReviewForReport($reviewId);

            if (! $lockedReview) {
                throw TestException::reviewNotAvailable();
            }

            if ((int) $lockedReview->reviewer_user_id === $reporterUserId) {
                throw TestException::cannotReportOwnReview();
            }

            $isNewReport = $this->repository->createReportOrTouch(
                reviewId: $reviewId,
                userId: $reporterUserId,
                reason: $reason,
                description: $description ? trim($description) : null
            );

            if (! $isNewReport) {
                return false ;
            }

            $reportsCount = $this->repository->countReportsForReview($reviewId);

            $shouldDelete = $this->thresholdPolicy->shouldDeleteReview(
                reportsCount: $reportsCount,
                yesCount: (int) $lockedReview->helpful_yes_count,
                noCount: (int) $lockedReview->helpful_no_count
            );

            if (! $shouldDelete) {
                return false;
            }

            $this->repository->deleteReview($reviewId);

            $this->repository->updateTestAfterReviewDeleted(
                testId: (int) $lockedReview->test_id,
                oldReviewsCount: (int) $lockedReview->reviews_count,
                oldAverage: (float) $lockedReview->average_rating,
                deletedRating: (int) $lockedReview->rating
            );

            $this->repository->refreshCreatorAverageTestRating(
                creatorUserId: (int) $lockedReview->creator_user_id
            );

            $eventPayload = [
                'test_id' => (int) $lockedReview->test_id,
                'creator_user_id' => (int) $lockedReview->creator_user_id,
                'actor_user_id' => $reporterUserId,
                'delta' => -1,
                'effective_at' => $lockedReview->created_at,
            ];

            $notificationPayload = [
                'review_owner_user_id' => (int) $lockedReview->reviewer_user_id,
                'review_id' => (int) $reviewId,
                'test_id' => (int) $lockedReview->test_id,
                'reason' => $reason,
                'reports_count' => (int) $reportsCount,
                'helpful_yes_count' => (int) $lockedReview->helpful_yes_count,
                'helpful_no_count' => (int) $lockedReview->helpful_no_count,
                'rating' => (int) $lockedReview->rating,
            ];

            Log::channel('audit')->info('Test review deleted automatically by report threshold.', [
                'test_review_id' => $reviewId,
                'test_id' => (int) $lockedReview->test_id,
                'reporter_user_id' => $reporterUserId,
                'reports_count' => $reportsCount,
                'helpful_yes_count' => (int) $lockedReview->helpful_yes_count,
                'helpful_no_count' => (int) $lockedReview->helpful_no_count,
            ]);

            return true ;
        });

        if ($eventPayload !== null) {
            event(new TestReviewStateChanged(...$eventPayload));
        }

        if (($isStatusChanged ?? false) === true && $notificationPayload !== null) {
            $this->sendReviewDeletedByReportsNotification($notificationPayload);
        }

        return [
            'is_status_changed' => $isStatusChanged ?? false,
        ];
    }

    private function sendReviewDeletedByReportsNotification(array $data): void
    {
        $payload = NotificationPayload::make(
            title: 'تم حذف تعليقك',
            body: 'تم حذف تعليقك على أحد الاختبارات بسبب وصول البلاغات إلى الحد المطلوب',
            metadata: [
                'type' => 'review_deleted_by_reports',
                'category' => 'report',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#FFE7E7',
                    'icon' => ImageProcessor::urlOrDefault('system-notification/trash.svg' , 'defaults/notification.svg' , 'public'),
                ],

                'actor' => null,

                'target' => [
                    'type' => 'test_review',
                    'id' => (int) $data['review_id'],
                    'title' => null,
                ],

                'navigation' => [
                    'screen' => 'public_test_details',
                    'action' => 'open',
                ],

                'params' => [
                    'test_id' => (int) $data['test_id'],
                    'review_id' => (int) $data['review_id'],
                ],
            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $data['review_owner_user_id'],
            payload: $payload,
        );
    }

}
