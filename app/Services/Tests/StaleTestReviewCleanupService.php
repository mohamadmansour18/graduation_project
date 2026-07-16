<?php

namespace App\Services\Tests;

use App\DTOs\Notifications\NotificationPayload;
use App\Enums\TestReviewStatus;
use App\Events\TestDashboardDeleted;
use App\Events\TestManagementStatusChanged;
use App\Helpers\ImageProcessor;
use App\Models\Test;
use App\Repositories\Tests\StaleTestReviewCleanupRepository;
use App\Services\Notifications\NotificationCenter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class StaleTestReviewCleanupService
{
    public function __construct(
        private readonly StaleTestReviewCleanupRepository $repository,
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
            'soft_deleted' => 0,
            'force_deleted' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($candidateIds as $testId) {
            try {
                $result = $this->processOne(
                    testId: $testId,
                    cutoff: $cutoff,
                    now: $now,
                );

                if (! $result) {
                    $summary['skipped']++;
                    continue;
                }

                $summary['processed']++;
                $summary[$result['deletion_type'] === 'soft_delete' ? 'soft_deleted' : 'force_deleted']++;

                $this->dispatchEvents($result);
                $this->sendOwnerNotification($result);

                Log::channel('audit')->info('stale_test_review_status_cleaned', $result);
            } catch (Throwable $exception) {
                $summary['failed']++;

                Log::channel('errors')->error('Failed to clean stale test review status', [
                    'test_id' => $testId,
                    'message' => $exception->getMessage(),
                    'exception_class' => $exception::class,
                ]);
            }
        }

        return $summary;
    }

    private function processOne(int $testId, CarbonImmutable $cutoff, CarbonImmutable $now): ?array
    {
        return DB::transaction(function () use ($testId, $cutoff, $now) {
            $test = $this->repository->findCandidateForUpdate(
                testId: $testId,
                cutoff: $cutoff,
            );

            if (! $test) {
                return null;
            }

            $fromStatus = $this->normalizeStatus($test->review_status);
            $deletionType = $this->deletionTypeFor($test, $fromStatus);
            $publishedAt = $test->published_at
                ? CarbonImmutable::parse($test->published_at, config('app.timezone'))
                : null;
            $shouldDecreasePublishCounters = ((int) ($test->current_approval_version ?? 0)) > 0;
            $openRound = null;
            $statusHistory = null;

            if ($deletionType === 'soft_delete') {
                $openRound = $this->repository->findLatestOpenReviewRoundForUpdate((int) $test->id);

                if ($openRound) {
                    $this->repository->closeReviewRoundAsDeleted(
                        round: $openRound,
                        decidedAt: $now,
                    );
                }

                $this->repository->markTestAsDeleted($test);

                $statusHistory = $this->repository->createStatusHistory(
                    testId: (int) $test->id,
                    reviewRoundId: $openRound?->id,
                    fromStatus: $fromStatus,
                    toStatus: TestReviewStatus::Deleted,
                    note: $this->deletionReason($fromStatus),
                );

                $this->repository->softDelete($test);
            } else {
                $this->repository->forceDelete($test);
            }

            return [
                'test_id' => (int) $test->id,
                'test_title' => (string) $test->title,
                'creator_user_id' => (int) $test->creator_user_id,
                'from_status' => $fromStatus->value,
                'to_status' => TestReviewStatus::Deleted->value,
                'deletion_type' => $deletionType,
                'deletion_reason' => $this->deletionReason($fromStatus),
                'deleted_at' => $now->toDateTimeString(),
                'deleted_date' => $now->toDateString(),
                'status_changed_at' => $test->current_status_changed_at,
                'review_round_id' => $openRound?->id,
                'status_history_id' => $statusHistory?->id,
                'should_appear_in_deleted_column' => $deletionType === 'soft_delete',
                'should_decrease_publish_counters' => $shouldDecreasePublishCounters,
                'published_year' => $publishedAt?->year,
                'published_month' => $publishedAt?->month,
            ];
        });
    }

    private function deletionTypeFor(Test $test, TestReviewStatus $status): string
    {
        if ($status !== TestReviewStatus::Reported) {
            return 'force_delete';
        }

        $isPaid = $test->price !== null && (float) $test->price > 0;
        $hasPaidPurchases = $this->repository->hasPaidPurchases((int) $test->id);

        return $isPaid && $hasPaidPurchases
            ? 'soft_delete'
            : 'force_delete';
    }

    private function normalizeStatus(mixed $status): TestReviewStatus
    {
        return $status instanceof TestReviewStatus
            ? $status
            : TestReviewStatus::from($status);
    }

    private function deletionReason(TestReviewStatus $status): string
    {
        return match ($status) {
            TestReviewStatus::New => 'تم حذف الاختبار تلقائيًا لأنه بقي في حالة مسودة لأكثر من 48 ساعة.',
            TestReviewStatus::Reported => 'تم حذف الاختبار تلقائيًا لأنه بقي في حالة مبلغ عنه لأكثر من 48 ساعة.',
            TestReviewStatus::NeedsRevision => 'تم حذف الاختبار تلقائيًا لأنه بقي في حالة يحتاج تعديل لأكثر من 48 ساعة.',
            default => 'تم حذف الاختبار تلقائيًا بسبب بقاء حالته معلقة لأكثر من 48 ساعة.',
        };
    }

    private function dispatchEvents(array $result): void
    {
        TestManagementStatusChanged::dispatch(
            $result['test_id'],
            $result['from_status'],
            $result['to_status'],
            $result['deleted_date'],
            $result['deleted_at'],
            0,
            $result['deletion_type'],
            $result['should_appear_in_deleted_column'],
        );

        TestDashboardDeleted::dispatch(
            $result['test_id'],
            $result['creator_user_id'],
            CarbonImmutable::parse($result['deleted_at'], config('app.timezone')),
            $result['published_year'],
            $result['published_month'],
            $result['should_decrease_publish_counters'],
        );
    }

    private function sendOwnerNotification(array $data): void
    {
        $testTitle = $data['test_title'] ?: 'اختبارك';

        $payload = NotificationPayload::make(
            title: 'تم حذف اختبارك تلقائيًا',
            body: "تم حذف اختبارك: {$testTitle}. السبب: {$data['deletion_reason']}",
            metadata: [
                'type' => 'stale_test_review_status_deleted',
                'category' => 'test_review',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#FFE7E7',
                    'icon' => ImageProcessor::urlOrDefault('system-notification/trash.svg', 'defaults/notification.svg', 'public'),
                ],

                'actor' => null,

                'navigation' => [
                    'screen' => 'my_tests_details',
                    'action' => 'open',
                ],

                'params' => [
                    'test_id' => (int) $data['test_id'],
                    'deletion_type' => $data['deletion_type'],
                ],
            ],
        );

        $this->notificationCenter->sendToUser(
            userId: (int) $data['creator_user_id'],
            payload: $payload,
        );
    }
}
