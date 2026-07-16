<?php

namespace App\Jobs;

use App\DTOs\Notifications\NotificationPayload;
use App\Models\FcmToken;
use App\Services\Notifications\FcmTokenService;
use App\Services\Notifications\FirebasePushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public array $backoff = [10, 30];

    public function __construct(
        private readonly array $userIds,
        private readonly array $payloadData,
        private readonly array $firebaseProjects,
    )
    {
        $this->onQueue('medium');
    }

    public function handle(FirebasePushService $firebasePushService , FcmTokenService $fcmTokenService): void
    {
        $userIds = collect($this->userIds)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($userIds)) {
            Log::warning('FCM skipped: empty user ids');

            return;
        }

        $payload = NotificationPayload::fromArray($this->payloadData);

        $tokens = $fcmTokenService->activeTokensForUsers(
            userIds: $userIds,
            firebaseProjects: $this->firebaseProjects,
        );

        if ($tokens->isEmpty()) {
            Log::info('FCM skipped: no active tokens found', [
                'user_ids' => $userIds,
                'notification_key' => $payload->notificationKey,
            ]);

            return;
        }

        $tokens
            ->groupBy('firebase_project')
            ->each(function ($projectTokens, string $firebaseProject) use ($firebasePushService, $payload) {
                try {
                    $result = $firebasePushService->sendToTokens(
                        firebaseProject: $firebaseProject,
                        payload: $payload,
                        tokens: $projectTokens->pluck('token')->all(),
                    );

                    Log::info('FCM notification sent', [
                        'firebase_project' => $firebaseProject,
                        'notification_key' => $payload->notificationKey,
                        'tokens_count' => $result['tokens_count'],
                        'success' => $result['success'],
                        'failed' => $result['failed'],
                        'invalid_tokens_count' => count($result['invalid_tokens']),
                        'unknown_tokens_count' => count($result['unknown_tokens']),
                    ]);
                } catch (Throwable $exception) {
                    Log::channel('errors')->error('FCM project send failed', [
                        'firebase_project' => $firebaseProject,
                        'notification_key' => $payload->notificationKey,
                        'message' => $exception->getMessage(),
                    ]);
                }
            });
    }
}
