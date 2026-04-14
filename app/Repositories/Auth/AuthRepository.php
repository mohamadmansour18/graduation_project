<?php

namespace App\Repositories\Auth;

use App\Enums\SystemRole;
use App\Models\AuthOtpCode;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuthRepository
{
    public function findUserByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->first();
    }

    public function findUserRoleByName(string $roleName): Builder|Model|null
    {
        return Role::query()
            ->where('name', $roleName)
            ->first();
    }

    public function createUser(array $data): User
    {
        return User::query()->create($data);
    }

    public function revokeActiveOtpCodes(int $userId, string $purpose): void
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
