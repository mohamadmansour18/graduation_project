<?php

namespace App\Services\Auth;

use App\Enums\BanType;
use App\Enums\PurposeOTP;
use App\Enums\SystemRole;
use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\AuthenticationException;
use App\Jobs\SendFailedLoginAlertJob;
use App\Jobs\SendOtpMailJob;
use App\Models\Role;
use App\Repositories\Auth\AuthRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        protected AuthRepository $authRepository
    ){}

    public function register(array $data):array
    {
        $existingUser = $this->authRepository->findUserByEmail($data['email']);

        if ($existingUser) {
            throw AuthenticationException::emailAlreadyRegistered();
        }

        $userRole = $this->authRepository->findUserRoleByName(SystemRole::Mobile_User->value);

        $otpCode = (string) random_int(100000, 999999);

        $result = DB::transaction(function () use ($data, $userRole, $otpCode){

            $user = $this->authRepository->createUser([
                'role_id' => $userRole->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'gender' => $data['gender'],
            ]);

            $this->authRepository->createOtpCode([
                'user_id' => $user->id,
                'purpose' => PurposeOTP::Email_Verification->value,
                'code_hash' => Hash::make($otpCode),
                'send_to_email' => $user->email,
                'expires_at' => now()->addMinutes(5),
                'consumed_at' => null,
                'revoked_at' => null,
                'attempts_count' => 0,
            ]);

            return $user;
        });

        SendOtpMailJob::dispatch($result , $otpCode , PurposeOTP::Email_Verification->value)->afterCommit();

        return [
            'user' => [
                'name' => $result->name,
                'email' => $result->email,
                'gender' => $result->gender,
            ],
            'otpCode' => $otpCode,
        ];
    }

    public function login(array $data):array
    {
        $user = $this->authRepository->findUserByEmailWithRelations($data['email']);

        if(!$user || ! Hash::check($data['password'], $user->password))
        {
            $this->handleFailedLoginAttempt($data['email'] , $user , $data['ip_address'] ?? null , $data['user_agent'] ?? null);
            throw AuthenticationException::invalidCredentials();
        }

        $this->authRepository->clearFailedLoginForEmail($data['email']);

        //verify user account it not ban
        $activeBan = $this->authRepository->getActiveBanForUser($user->id);

        if($activeBan)
        {
            Log::warning("محاولة تسجيل دخول لحساب محظور" , [
                'user_id' => $user->id,
                'email' => $user->email,
                'ban_type' => $activeBan->ban_type,
            ]);

            throw AuthenticationException::accountBanned(
                reason: $activeBan->reason ?? "لم يتم تحديد سبب الحظر لحسابك" ,
                startsAt: $activeBan->starts_at?->toDateTimeString() ?? '',
                endsAt: $activeBan->ends_at?->toDateTimeString() ?? '',
                isPermanent: $activeBan->ban_type === BanType::Permanent
            );
        }


        if($user->isMobileUser()) {
            //verify that user verification his email
            if (is_null($user->email_verified_at)) {
                throw AuthenticationException::emailNotVerified();
            }

            //verify that user completed onboarding
            if (is_null($user->onboarding_completed_at)) {

                $lastCompletedStep = $user->userOnboardingProfile?->last_completed_step;

                throw AuthenticationException::onboardingIncomplete($lastCompletedStep);
            }
        }

        //Generate Token
        $token = auth('api')->login($user);

        //refresh last login at
        $this->authRepository->updateLastLoginAt($user->id);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'gender' => $user->gender,
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
