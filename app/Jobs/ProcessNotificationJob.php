<?php

namespace App\Jobs;

use App\DTOs\Notifications\NotificationPayload;
use App\Models\User;
use App\Notifications\AppDatabaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as LaravelNotification;
class ProcessNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [10, 30];
    public function __construct(
        private readonly array $userIds,
        private readonly array $payloadData,
        private readonly array $firebaseProjects,
    )
    {}

    public function handle(): void
    {
        $userIds = collect($this->userIds)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($userIds)) {
            Log::warning('Notification skipped: empty user ids');

            return;
        }

        $payload = NotificationPayload::fromArray($this->payloadData);

        $users = User::query()
            ->whereIn('id', $userIds)
            ->get();

        if ($users->isEmpty()) {
            Log::warning('Notification skipped: no users found', [
                'user_ids' => $userIds,
                'notification_key' => $payload->notificationKey,
            ]);

            return;
        }

        DB::transaction(function () use ($users, $userIds, $payload) {

            //store in database
            LaravelNotification::send(
                $users,
                new AppDatabaseNotification($payload)
            );

            //after success storage notification call second job to send FCM notification
            SendFcmNotificationJob::dispatch(
                userIds: $userIds,
                payloadData: $payload->toArray(),
                firebaseProjects: $this->firebaseProjects,
            )
                ->onQueue(config('app_notifications.queue', 'default'))
                ->afterCommit();
        });

        Log::info('Database notification stored', [
            'users_count' => $users->count(),
            'notification_key' => $payload->notificationKey,
        ]);
    }

}
