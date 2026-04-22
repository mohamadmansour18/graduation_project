<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reset_Password\RequestPasswordResetOtpRequest;
use App\Http\Requests\Reset_Password\ResetPasswordRequest;
use App\Http\Requests\Reset_Password\VerifyPasswordResetOtpRequest;
use App\Services\Auth\PasswordResetService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    use ApiResponse;

    Public function __construct(
        protected PasswordResetService $passwordResetService
    ){}

    public function requestPasswordResetOtp(RequestPasswordResetOtpRequest $request): JsonResponse
    {
        $this->passwordResetService->requestPasswordResetOtp($request->validated('email'));

        return $this->successResponse(
            title: 'تمت العملية بنجاح',
            message: 'إذا كان البريد الإلكتروني مسجلًا لدينا، فسيتم إرسال رمز التحقق إليه'
        );
    }

    public function verifyPasswordResetOtp(VerifyPasswordResetOtpRequest $request): JsonResponse
    {
        $this->passwordResetService->verifyPasswordResetOtp($request->validated('email'), $request->validated('otp_code'),);

        return $this->successResponse(
            title: 'تم التحقق بنجاح',
            message: 'تم التحقق من رمز إعادة تعيين كلمة المرور بنجاح'
        );
    }

    public function resendPasswordResetOtp(RequestPasswordResetOtpRequest $request): JsonResponse
    {
        $this->passwordResetService->requestPasswordResetOtp($request->validated('email'));

        return $this->successResponse(
            title: 'تمت العملية بنجاح',
            message: 'إذا كان البريد الإلكتروني مسجلًا لدينا، فسيتم إرسال رمز تحقق جديد إليه.'
        );
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->passwordResetService->resetPassword($request->validated('email'), $request->validated('otp_code'), $request->validated('password'),);

        return $this->successResponse(
            title: 'تمت العملية بنجاح',
            message: 'تمت إعادة تعيين كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول باستخدام كلمة المرور الجديدة'
        );
    }

}
