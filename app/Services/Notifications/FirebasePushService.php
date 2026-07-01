<?php

namespace App\Services\Notifications;

use App\DTOs\Notifications\NotificationPayload;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Throwable;

class FirebasePushService
{
    public function sendToTokens(string $firebaseProject, NotificationPayload $payload, array $tokens,): array
    {
        $tokens = collect($tokens)
            ->filter()
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return [
                'project' => $firebaseProject,
                'tokens_count' => 0,
                'success' => 0,
                'failed' => 0,
                'invalid_tokens' => [],
                'unknown_tokens' => [],
            ];
        }

        $success = 0;
        $failed = 0;
        $invalidTokens = [];
        $unknownTokens = [];

        $messaging = Firebase::project($firebaseProject)->messaging();

        $message = CloudMessage::new()
            ->withNotification(
                FirebaseNotification::create(
                    $payload->title,
                    $payload->body,
                )
            )
            ->withData($payload->toFcmDataArray());

        $chunkSize = (int) config('app_notifications.fcm_chunk_size', 500);

        foreach ($tokens->chunk($chunkSize) as $chunk) {
            try {
                $chunkTokens = $chunk->values()->all();

                $report = $messaging->sendMulticast($message, $chunkTokens);

                $success += $report->successes()->count();
                $failed += $report->failures()->count();

                foreach ($report->failures()->getItems() as $failure) {
                    Log::warning('FCM token send failure detail', [
                        'firebase_project' => $firebaseProject,
                        'notification_key' => $payload->notificationKey,
                        'error_message' => $failure->error()->getMessage(),
                    ]);
                }

                $invalidTokens = array_merge(
                    $invalidTokens,
                    $report->invalidTokens()
                );

                $unknownTokens = array_merge(
                    $unknownTokens,
                    $report->unknownTokens()
                );
            } catch (Throwable $exception) {
                $failed += $chunk->count();

                Log::channel('errors')->error('FCM chunk send failed', [
                    'firebase_project' => $firebaseProject,
                    'notification_key' => $payload->notificationKey,
                    'tokens_count' => $chunk->count(),
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $tokensToRevoke = array_values(array_unique(array_merge(
            $invalidTokens,
            $unknownTokens,
        )));

        if (! empty($tokensToRevoke)) {
            $this->revokeTokens($tokensToRevoke);
        }

        return [
            'project' => $firebaseProject,
            'tokens_count' => $tokens->count(),
            'success' => $success,
            'failed' => $failed,
            'invalid_tokens' => $invalidTokens,
            'unknown_tokens' => $unknownTokens,
        ];
    }

    private function revokeTokens(array $tokens): void
    {
        FcmToken::query()
            ->whereIn('token', $tokens)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
