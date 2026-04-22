<?php

namespace App\Repositories\Auth;

use App\Models\AuthOtpCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PasswordResetRepository
{
    //-----------------[FIRST API]-----------------//
    public function findUserByEmail(string $email): ?User
    {
        return User::query()
            ->select(['id', 'name', 'email', 'password', 'email_verified_at'])
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

    //-----------------[SECOND API]-----------------//

    public function findLatestActiveOtpByPurpose(int $userId, string $purpose): Builder|Model|null
    {
        return AuthOtpCode::query()
            ->where('user_id', $userId)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->latest('id')
            ->first();
    }

    //-----------------[THIRD API]-----------------//

    public function updateUserPassword(int $userId, string $hashedPassword): void
    {
        User::query()
            ->whereKey($userId)
            ->update([
                'password' => $hashedPassword,
                'updated_at' => now(),
            ]);
    }

    public function consumeOtpCode(int $otpId): void
    {
        AuthOtpCode::query()
            ->whereKey($otpId)
            ->update([
                'consumed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
