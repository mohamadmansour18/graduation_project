<?php

namespace App\Repositories\Admin;

use App\Models\FailedLogin;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AuthRepository
{

    public function findUserByEmailWithRelations(string $email): ?User
    {
        return User::query()
            ->with([
                'role:id,name',
            ])
            ->where('email', $email)
            ->first();
    }

    public function clearFailedLoginForEmail(string $email): void
    {
        FailedLogin::query()
            ->where('email', $email)
            ->delete();
    }

    public function updateLastLoginAt(int $userId): void
    {
        User::query()
            ->whereKey($userId)
            ->update([
                'last_login_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function findFailedLoginByEmail(string $email): FailedLogin|Builder|null
    {
        return FailedLogin::query()
            ->where('email', $email)
            ->first();
    }

    public function createFailedLogin(array $data): FailedLogin
    {
        return FailedLogin::query()->create($data);
    }

    public function incrementFailedLogin(FailedLogin $failedLogin, bool $resetWindow = false , ?string $ipAddress = null , ?string $userAgent = null): FailedLogin
    {
        if ($resetWindow) {
            $failedLogin->update([
                'attempts_count' => 1,
                'window_started_at' => now(),
                'last_attempt_at' => now(),
                'last_ip_address' => $ipAddress,
                'last_user_agent' => $userAgent,
            ]);

            return $failedLogin->refresh();
        }

        $failedLogin->increment('attempts_count');
        $failedLogin->update([
            'last_attempt_at' => now(),
            'last_ip_address' => $ipAddress,
            'last_user_agent' => $userAgent,
        ]);

        return $failedLogin->refresh();
    }

    public function markFailedLoginNotified(FailedLogin $failedLogin): void
    {
        $failedLogin->update([
            'last_notified_at' => now(),
        ]);
    }
}
