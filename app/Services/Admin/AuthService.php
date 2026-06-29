<?php

namespace App\Services\Admin;

use App\Exceptions\Api\AuthenticationException;
use App\Helpers\ImageProcessor;
use App\Jobs\SendFailedLoginAlertJob;
use App\Repositories\Admin\AuthRepository;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        private readonly AuthRepository $authRepository
    )
    {}
    public function login(array $data):array
    {
        $user = $this->authRepository->findUserByEmailWithRelations($data['email']);

        if(!$user || ! Hash::check($data['password'], $user->password))
        {
            $this->handleFailedLoginAttempt($data['email'] , $user , $data['ip_address'] ?? null , $data['user_agent'] ?? null);
            throw AuthenticationException::invalidCredentials();
        }

        $this->authRepository->clearFailedLoginForEmail($data['email']);

        //Generate Token
        $token = auth('api')->login($user);

        //refresh last login at
        $this->authRepository->updateLastLoginAt($user->id);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role->name,
                'photo' => ImageProcessor::urlOrDefault($user->userProfile?->avatar_path , 'defaults/default-avatar.svg' , $user->userProfile?->avatar_disk),
            ],
            'token' => $token,
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ];
    }

    protected function handleFailedLoginAttempt(string $email, $user = null, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        $failedLogin = $this->authRepository->findFailedLoginByEmail($email);

        if(!$failedLogin){
            $failedLogin = $this->authRepository->createFailedLogin([
                'email' => $email,
                'user_id' => $user?->id,
                'attempts_count' => 1,
                'window_started_at' => now(),
                'last_attempt_at' => now(),
                'last_notified_at' => null,
                'last_ip_address' => $ipAddress,
                'last_user_agent' => $userAgent,
            ]);
        } else {
            $windowExpired = $failedLogin->window_started_at->lt(now()->subMinutes(15));

            $failedLogin = $this->authRepository->incrementFailedLogin(
                failedLogin: $failedLogin,
                resetWindow: $windowExpired,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );
        }

        if($user && $failedLogin->attempts_count >= 3 && (is_null($failedLogin->last_notified_at) || $failedLogin->last_notified_at->lt($failedLogin->window_started_at)))
        {
            SendFailedLoginAlertJob::dispatch(
                user: $user,
                attemptsCount: $failedLogin->attempts_count,
                ipAddress: $failedLogin->last_ip_address,
                userAgent: $failedLogin->last_user_agent,
            )->afterCommit();

            $this->authRepository->markFailedLoginNotified($failedLogin);
        }
    }
}
