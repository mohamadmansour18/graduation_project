<?php

namespace App\Services\Auth;

use App\Enums\PurposeOTP;
use App\Exceptions\Api\PasswordResetException;
use App\Jobs\SendOtpMailJob;
use App\Repositories\Auth\PasswordResetRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PasswordResetService
{
    public function __construct(
        protected PasswordResetRepository $passwordResetRepository
    )
    {}

    public function requestPasswordResetOtp(string $email): void
    {
        $user = $this->passwordResetRepository->findUserByEmail($email);

        if (!$user) {
            Log::channel('audit')->warning('تم طلب OTP لإعادة تعيين كلمة المرور لبريد غير موجود', [
                'email' => $email,
            ]);
            return;
        }

        if(is_null($user->email_verified_at))
        {
            throw PasswordResetException::emailNotVerified();
        }

        $otp = (string) random_int(100000, 999999);

        DB::transaction(function () use ($user , $otp) {
            $this->passwordResetRepository->revokeActiveOtpCodesByPurpose($user->id , PurposeOTP::Password_Reset->value);

            $this->passwordResetRepository->createOtpCode([
                'user_id' => $user->id,
                'purpose' => PurposeOTP::Password_Reset->value,
                'code_hash' => Hash::make($otp),
                'send_to_email' => $user->email,
                'expires_at' => now()->addMinutes(5),
                'consumed_at' => null,
                'revoked_at' => null,
            ]);
        });

        SendOtpMailJob::dispatch($user , $otp , PurposeOTP::Password_Reset->value)->afterCommit();

    }

    public function verifyPasswordResetOtp(string $email, string $otpCode): void
    {
        $user = $this->passwordResetRepository->findUserByEmail($email);

        if (!$user) {
            throw PasswordResetException::userNotFound();
        }

        $otpRecord = $this->passwordResetRepository->findLatestActiveOtpByPurpose($user->id , PurposeOTP::Password_Reset->value);

        if (!$otpRecord) {
            throw PasswordResetException::invalidResetOtp();
        }

        if(! Hash::check($otpCode , $otpRecord->code_hash))
        {
            throw PasswordResetException::invalidResetOtp();
        }

        if($otpRecord->expires_at->isPast())
        {
            throw PasswordResetException::expiredResetOtp();
        }
    }

    public function resetPassword(string $email, string $otpCode, string $newPassword): void
    {
        $user = $this->passwordResetRepository->findUserByEmail($email);
        if (!$user) {
            throw PasswordResetException::userNotFound();
        }

        $otpRecord = $this->passwordResetRepository->findLatestActiveOtpByPurpose($user->id , PurposeOTP::Password_Reset->value);

        if (!$otpRecord) {
            throw PasswordResetException::invalidResetOtp();
        }


        if(! Hash::check($otpCode , $otpRecord->code_hash))
        {
            throw PasswordResetException::invalidResetOtp();
        }

        if(Hash::check($newPassword , $user->password))
        {
            throw PasswordResetException::invalidNewPassword();
        }

        DB::transaction(function () use ($user, $otpRecord, $newPassword) {
            $this->passwordResetRepository->updateUserPassword($user->id, Hash::make($newPassword));

            $this->passwordResetRepository->consumeOtpCode($otpRecord->id);

        });
    }
}
