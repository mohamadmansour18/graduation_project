<?php

namespace App\Services\Auth;

use App\Enums\PurposeOTP;
use App\Enums\SystemRole;
use App\Exceptions\Api\AuthenticationException;
use App\Jobs\SendOtpMailJob;
use App\Models\Role;
use App\Repositories\Auth\AuthRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
            'user' => $result,
            'otpCode' => $otpCode,
        ];
    }
}
