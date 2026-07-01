<?php

namespace App\Services\Notifications;

use App\Enums\FirebaseProject;
use App\Enums\NotificationPlatform;
use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class FcmTokenService
{
    public function upsertForUser(User $user, string $token, string|NotificationPlatform $platform, string|FirebaseProject $firebaseProject, ?string $deviceId = null, ?string $deviceName = null, ?string $userAgent = null,): FcmToken
    {
        $token = trim($token);

        if ($token === '') {
            throw new InvalidArgumentException('FCM token cannot be empty.');
        }

        $platformValue = $this->normalizePlatform($platform);
        $firebaseProjectValue = $this->normalizeFirebaseProject($firebaseProject);

        return DB::transaction(function () use (
            $user,
            $token,
            $platformValue,
            $firebaseProjectValue,
            $deviceId,
            $deviceName,
            $userAgent
        ) {
            $fcmToken = FcmToken::query()->updateOrCreate(
                ['token' => $token],
                [
                    'user_id' => $user->id,
                    'platform' => $platformValue,
                    'firebase_project' => $firebaseProjectValue,
                    'device_id' => $deviceId,
                    'device_name' => $deviceName,
                    'user_agent' => $userAgent,
                    'last_used_at' => now(),
                    'revoked_at' => null,
                ]
            );

            if (! empty($deviceId)) {
                $this->revokeOldTokensForSameDevice(
                    user: $user,
                    currentToken: $token,
                    deviceId: $deviceId,
                    firebaseProject: $firebaseProjectValue,
                );
            }

            $this->forgetUserTokensCache($user->id);

            Log::info('FCM token stored or refreshed', [
                'user_id' => $user->id,
                'platform' => $platformValue,
                'firebase_project' => $firebaseProjectValue,
                'device_id' => $deviceId,
                'token_hash' => sha1($token),
            ]);

            return $fcmToken;
        });
    }

    public function revokeForUserByToken(User $user, string $token, ?string $firebaseProject = null,): int
    {
        $token = trim($token);

        if ($token === '') {
            return 0;
        }

        $query = FcmToken::query()
            ->where('user_id', $user->id)
            ->where('token', $token)
            ->whereNull('revoked_at');

        if (! empty($firebaseProject)) {
            $query->where(
                'firebase_project',
                $this->normalizeFirebaseProject($firebaseProject)
            );
        }

        $updatedCount = $query->update([
            'revoked_at' => now(),
            'updated_at' => now(),
        ]);

        if ($updatedCount > 0) {
            $this->forgetUserTokensCache($user->id);

            Log::info('FCM token revoked by token', [
                'user_id' => $user->id,
                'firebase_project' => $firebaseProject,
                'token_hash' => sha1($token),
            ]);
        }

        return $updatedCount;
    }

    public function revokeForUserByDeviceId(User $user, string $deviceId, ?string $firebaseProject = null,): int
    {
        $deviceId = trim($deviceId);

        if ($deviceId === '') {
            return 0;
        }

        $query = FcmToken::query()
            ->where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->whereNull('revoked_at');

        if (! empty($firebaseProject)) {
            $query->where(
                'firebase_project',
                $this->normalizeFirebaseProject($firebaseProject)
            );
        }

        $updatedCount = $query->update([
            'revoked_at' => now(),
            'updated_at' => now(),
        ]);

        if ($updatedCount > 0) {
            $this->forgetUserTokensCache($user->id);

            Log::info('FCM token revoked by device id', [
                'user_id' => $user->id,
                'firebase_project' => $firebaseProject,
                'device_id' => $deviceId,
            ]);
        }

        return $updatedCount;
    }

    public function revokeAllForUser(User $user, ?string $firebaseProject = null,): int
    {
        $query = FcmToken::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at');

        if (! empty($firebaseProject)) {
            $query->where(
                'firebase_project',
                $this->normalizeFirebaseProject($firebaseProject)
            );
        }

        $updatedCount = $query->update([
            'revoked_at' => now(),
            'updated_at' => now(),
        ]);

        if ($updatedCount > 0) {
            $this->forgetUserTokensCache($user->id);

            Log::info('All FCM tokens revoked for user', [
                'user_id' => $user->id,
                'firebase_project' => $firebaseProject,
                'revoked_count' => $updatedCount,
            ]);
        }

        return $updatedCount;
    }

    public function activeTokensForUsers(array $userIds, array $firebaseProjects = []): Collection
    {
        $userIds = collect($userIds)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($userIds)) {
            return new Collection();
        }

        $query = FcmToken::query()
            ->active()
            ->whereIn('user_id', $userIds);

        if (! empty($firebaseProjects)) {
            $query->whereIn(
                'firebase_project',
                collect($firebaseProjects)
                    ->map(fn (string $project) => $this->normalizeFirebaseProject($project))
                    ->unique()
                    ->values()
                    ->all()
            );
        }

        return $query->get([
            'id',
            'user_id',
            'token',
            'platform',
            'firebase_project',
        ]);
    }

    private function revokeOldTokensForSameDevice(User $user, string $currentToken, string $deviceId, string $firebaseProject,): void {
        FcmToken::query()
            ->where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->where('firebase_project', $firebaseProject)
            ->where('token', '!=', $currentToken)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function normalizePlatform(string|NotificationPlatform $platform): string
    {
        $value = $platform instanceof NotificationPlatform
            ? $platform->value
            : $platform;

        if (! in_array($value, NotificationPlatform::values(), true)) {
            throw new InvalidArgumentException("Invalid notification platform: {$value}");
        }

        return $value;
    }

    private function normalizeFirebaseProject(string|FirebaseProject $firebaseProject): string
    {
        $value = $firebaseProject instanceof FirebaseProject
            ? $firebaseProject->value
            : $firebaseProject;

        if (! in_array($value, FirebaseProject::values(), true)) {
            throw new InvalidArgumentException("Invalid Firebase project: {$value}");
        }

        return $value;
    }

    private function forgetUserTokensCache(int $userId): void
    {
        Cache::forget("user:{$userId}:fcm_tokens");
    }
}
