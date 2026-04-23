<?php

namespace App\Repositories\Auth;

use App\Enums\BanType;
use App\Enums\SystemRole;
use App\Models\AuthOtpCode;
use App\Models\FailedLogin;
use App\Models\Role;
use App\Models\User;
use App\Models\UserBan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuthRepository
{
    //--------------------------------[REGISTER]--------------------------------//
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

    public function createOtpCode(array $data): AuthOtpCode
    {
        return AuthOtpCode::query()->create($data);
    }

    //--------------------------------[LOGIN]--------------------------------//

    public function findUserByEmailWithRelations(string $email): ?User
    {
        return User::query()
            ->with([
                'role:id,name',
                'userOnboardingProfile:id,user_id,last_completed_step',
            ])
            ->where('email', $email)
            ->first();
    }

    public function getActiveBanForUser(int $userId): ?object
    {
        return UserBan::query()
            ->where('user_id', $userId)
            ->whereNull('lifted_at')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('ban_type', BanType::Permanent->value)
                        ->where('starts_at', '<=', now());
                })->orWhere(function ($q) {
                    $q->where('ban_type', BanType::Temporary->value)
                        ->where('starts_at', '<=', now())
                        ->where('ends_at', '>=', now());
                });
            })
            ->latest('starts_at')
            ->first();
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

    public function clearFailedLoginForEmail(string $email): void
    {
        FailedLogin::query()
            ->where('email', $email)
            ->delete();
    }

    //--------------------------------[VERIFY]--------------------------------//
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

    public function consumeOtpCode(int $otpId): void
    {
        AuthOtpCode::query()
            ->whereKey($otpId)
            ->update([
                'consumed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function markEmailAsVerified(int $userId): void
    {
        User::query()
            ->whereKey($userId)
            ->update([
                'email_verified_at' => now(),
                'updated_at' => now(),
            ]);
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


}
