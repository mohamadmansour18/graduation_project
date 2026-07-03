<?php

namespace App\Services\Notifications;

use App\DTOs\Notifications\NotificationPayload;
use App\Enums\FirebaseProject;
use App\Jobs\ProcessNotificationJob;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class NotificationCenter
{
    public function sendToUser(int $userId, NotificationPayload $payload, ?array $firebaseProjects = null): void
    {
        $this->sendToUsers(
            userIds: [$userId],
            payload: $payload,
            firebaseProjects: $firebaseProjects,
        );
    }

    public function sendToUsers(array $userIds, NotificationPayload $payload, ?array $firebaseProjects = null,): void
    {
        $userIds = collect($userIds)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($userIds)) {
            Log::warning('NotificationCenter skipped: empty user ids', [
                'notification_key' => $payload->notificationKey,
            ]);

            return;
        }

        $projects = $this->normalizeFirebaseProjects($firebaseProjects);

        ProcessNotificationJob::dispatch(
            userIds: $userIds,
            payloadData: $payload->toArray(),
            firebaseProjects: $projects,
        )
            ->onQueue(config('app_notifications.queue', 'default'))
            ->afterCommit();

        Log::info('NotificationCenter dispatched notification job', [
            'users_count' => count($userIds),
            'firebase_projects' => $projects,
            'notification_key' => $payload->notificationKey,
        ]);
    }

    public function sendToMobile(array|int $userIds, NotificationPayload $payload,): void
    {
        $this->sendToUsers(
            userIds: is_array($userIds) ? $userIds : [$userIds],
            payload: $payload,
            firebaseProjects: [FirebaseProject::Mobile->value],
        );
    }

    public function sendToWeb(array|int $userIds, NotificationPayload $payload): void
    {
        $this->sendToUsers(
            userIds: is_array($userIds) ? $userIds : [$userIds],
            payload: $payload,
            firebaseProjects: [FirebaseProject::Web->value],
        );
    }

    private function normalizeFirebaseProjects(?array $firebaseProjects): array
    {
        $allowedProjects = FirebaseProject::values();

        $projects = $firebaseProjects ?: $allowedProjects;

        $projects = collect($projects)
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($projects as $project) {
            if (! in_array($project, $allowedProjects, true)) {
                throw new InvalidArgumentException("Invalid Firebase project: {$project}");
            }
        }

        return $projects;
    }
}
