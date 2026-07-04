<?php

namespace App\Services\Notifications;

use App\DTOs\Notifications\NotificationPayload;
use App\Helpers\ImageProcessor;
use App\Repositories\Notification\StudyPlanScheduledNotificationRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

class StudyPlanScheduledNotificationService
{
    public function __construct(
        private readonly StudyPlanScheduledNotificationRepository $repository,
        private readonly NotificationCenter $notificationCenter,
    ) {}

    public function sendDueTaskStartNotifications(): int
    {
        $now = CarbonImmutable::now(config('app.timezone'))->startOfMinute();

        $from = $now;
        $to = $now->addMinute();

        $occurrences = $this->repository->findTaskStartOccurrencesDueBetween(
            from: $from,
            to: $to,
        );

        $sentCount = 0;

        foreach ($occurrences as $occurrence) {
            $deliveryKey = $this->taskStartDeliveryKey($occurrence);

            $reserved = $this->repository->reserveDelivery(
                userId: (int) $occurrence->user_id,
                deliveryKey: $deliveryKey,
                notificationType: 'study_task_started',
                deliverAt: $from,
                context: [
                    'occurrence_id' => (int) $occurrence->occurrence_id,
                    'task_id' => (int) $occurrence->task_id,
                    'study_plan_id' => (int) $occurrence->study_plan_id,
                ],
            );

            if (! $reserved) {
                continue;
            }

            $payload = $this->makeTaskStartPayload($occurrence);

            $this->notificationCenter->sendToMobile(
                userIds: (int) $occurrence->user_id,
                payload: $payload,
            );

            $this->repository->markAsDispatched($deliveryKey);

            $sentCount++;
        }

//        Log::channel('daily')->info('study_task_start_notifications_processed', [
//            'from' => $from->toDateTimeString(),
//            'to' => $to->toDateTimeString(),
//            'candidates_count' => $occurrences->count(),
//            'sent_count' => $sentCount,
//        ]);

        return $sentCount;
    }

    public function sendDueTaskMissedNotifications(): int
    {
        $now = CarbonImmutable::now(config('app.timezone'))->startOfMinute();

        $tasks = $this->repository->findDueTasksToMarkAsMissed(
            now: $now,
        );

        $processedCount = 0;

        foreach ($tasks as $task) {
            $missedTask = $this->repository->markTaskAsMissedIfStillNotCompleted(
                taskId: (int) $task->task_id,
                missedAt: $now,
            );

            if ($missedTask === null) {
                continue;
            }

            $deliveryKey = "study_task_missed:{$missedTask->task_id}:{$missedTask->deadline_at}";

            $reserved = $this->repository->reserveDelivery(
                userId: (int) $missedTask->user_id,
                deliveryKey: $deliveryKey,
                notificationType: 'study_task_missed_reminder',
                deliverAt: $now,
                context: [
                    'task_id' => (int) $missedTask->task_id,
                    'study_plan_id' => (int) $missedTask->study_plan_id,
                    'deadline_at' => $missedTask->deadline_at,
                ],
            );

            if (! $reserved) {
                continue;
            }

            $payload = $this->makeTaskMissedPayload($missedTask);

            $this->notificationCenter->sendToMobile(
                userIds: (int) $missedTask->user_id,
                payload: $payload,
            );

            $this->repository->markAsDispatched($deliveryKey);

            $processedCount++;
        }

//        Log::channel('daily')->info('study_task_missed_notifications_processed', [
//        'now' => $now->toDateTimeString(),
//        'candidates_count' => $tasks->count(),
//        'processed_count' => $processedCount,
//        ]);

        return $processedCount;
    }

    public function sendDailyStudyMotivationNotifications(): int
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();

        $users = $this->repository->findUsersWhoHaveTasksToday($today);

        $sentCount = 0;

        foreach ($users as $user) {
            $userId = (int) $user->user_id;

            $deliveryKey = "daily_study_motivation:{$userId}:{$today->toDateString()}";

            $reserved = $this->repository->reserveDelivery(
                userId: $userId,
                deliveryKey: $deliveryKey,
                notificationType: 'daily_study_tasks_motivation',
                deliverAt: $today,
                context: [
                    'date' => $today->toDateString(),
                    'tasks_count' => (int) $user->tasks_count,
                ],
            );

            if (! $reserved) {
                continue;
            }

            $payload = NotificationPayload::make(
                title: 'يوم دراسي جديد بانتظارك',
                body: 'لديك مهام دراسية اليوم، حافظ على تقدمك ولا تؤجل إنجازك',
                metadata: [
                    'type' => 'daily_study_tasks_motivation',
                    'category' => 'study_plan',

                    'presentation' => [
                        'mode' => 'system',
                        'floor_color' => '#E4FFE5',
                        'icon' => ImageProcessor::urlOrDefault(
                            'system-notification/task.svg',
                            'defaults/notification.svg',
                            'public'
                        ),
                    ],

                    'actor' => null,

                    'navigation' => [
                        'screen' => 'study_plan_today',
                        'action' => 'open',
                    ],

                    'params' => [
                        'date' => $today->toDateString(),
                    ],
                ],
            );

            $this->notificationCenter->sendToMobile(
                userIds: $userId,
                payload: $payload,
            );

            $this->repository->markAsDispatched($deliveryKey);

            $sentCount++;
        }

//        Log::channel('daily')->info('daily_study_motivation_notifications_processed', [
//            'date' => $today->toDateString(),
//            'users_count' => $users->count(),
//            'sent_count' => $sentCount,
//        ]);

        return $sentCount;
    }

    private function makeTaskStartPayload(object $occurrence): NotificationPayload
    {
        return NotificationPayload::make(
            title: 'حان وقت مهمتك الدراسية',
            body: "حان وقت البدء بمهمة: {$occurrence->task_title}",
            metadata: [
                'type' => 'study_task_started',
                'category' => 'study_plan',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#FEE4FF',
                    'icon' => ImageProcessor::urlOrDefault(
                        'system-notification/alarm.svg',
                        'defaults/notification.svg',
                        'public'
                    ),
                ],

                'actor' => null,

                'navigation' => [
                    'screen' => 'study_task_details',
                    'action' => 'open',
                ],

                'params' => [
                    'occurrence_id' => (int) $occurrence->occurrence_id,
                    'task_id' => (int) $occurrence->task_id,
                    'study_plan_id' => (int) $occurrence->study_plan_id,
                    'date' => (string) $occurrence->occurrence_date,
                ],
            ],
        );
    }

    private function makeTaskMissedPayload(object $task): NotificationPayload
    {
        return NotificationPayload::make(
            title: 'فات وقت المهمة',
            body: "يبدو أنك لم تُنجز مهمة: {$task->task_title}. لا بأس، حاول تعويضها اليوم.",
            metadata: [
                'type' => 'study_task_missed_reminder',
                'category' => 'study_plan',

                'presentation' => [
                    'mode' => 'system',
                    'floor_color' => '#FFE7E7',
                    'icon' => ImageProcessor::urlOrDefault(
                        'system-notification/miss.svg',
                        'defaults/notification.svg',
                        'public'
                    ),
                ],

                'actor' => null,

                'navigation' => [
                    'screen' => 'study_task_details',
                    'action' => 'open',
                ],

                'params' => [
                    'task_id' => (int) $task->task_id,
                    'study_plan_id' => (int) $task->study_plan_id,
                    'status' => 'missed',
                    'deadline_at' => $task->deadline_at,
                    'missed_at' => $task->missed_at,
                ],
            ],
        );
    }

    private function taskStartDeliveryKey(object $occurrence): string
    {
        return implode(':', [
            'study_task_started',
            $occurrence->occurrence_id,
            $occurrence->occurrence_date,
            $occurrence->scheduled_start_time,
        ]);
    }

    private function taskMissedDeliveryKey(object $occurrence): string
    {
        return implode(':', [
            'study_task_missed',
            $occurrence->occurrence_id,
            $occurrence->occurrence_date,
            $occurrence->scheduled_end_time,
        ]);
    }
}
