<?php

namespace App\Repositories\Auth;

use App\Models\AuthOtpCode;
use App\Models\User;

class PasswordResetRepository
{
    public function findUserByEmail(string $email): ?User
    {
        return User::query()
            ->select(['id', 'name', 'email'])
            ->where('email', $email)
            ->first();
    }

    public function revokeActiveOtpCodesByPurpose(int $userId, string $purpose): void
    {
        AuthOtpCode::query()
            ->where('user_id', $userId)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function createOtpCode(array $data): AuthOtpCode
    {
        return AuthOtpCode::query()->create($data);
    }
}
