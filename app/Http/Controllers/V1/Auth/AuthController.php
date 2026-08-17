<?php

namespace App\Http\Controllers\V1\Auth;

use App\Enums\FirebaseProject;
use App\Enums\NotificationPlatform;
use App\Exceptions\Jwt\TokenMissingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyEmailOtpRequest;
use App\Http\Requests\Reset_Password\RequestPasswordResetOtpRequest;
use App\Services\Auth\AuthService;
use App\Services\Notifications\FcmTokenService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuthService $authService
    ){}

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $this->authService->register($request->validated());

        return $this->dataResponse(
            data: $data,
            title: "تم انشاء حساب المستخدم بنجاح",
            statusCode: 201,
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $payLoad = [
            ...$request->validated(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),

            'fcm_platform' => NotificationPlatform::Mobile->value,
            'firebase_project' => FirebaseProject::Mobile->value,
        ];

        $data = $this->authService->login($payLoad);

        return $this->dataResponse(
            data: $data,
            title: "تم تسجيل الدخول بنجاح",
        );
    }

    public function refresh()
    {
        $token = request()->bearerToken();

        if (! $token) {
            throw new TokenMissingException();
        }

        $newToken = JWTAuth::setToken($token)->refresh();
        $user = JWTAuth::setToken($newToken)->authenticate();

        if (! $user) {
            throw new TokenMissingException();
        }

        $this->authService->assertUserIsNotBanned($user);

        return $this->dataResponse([
            'newToken' => $newToken,
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ], 'تم تحديث التوكن بنجاح');
    }

    public function verifyEmail(VerifyEmailOtpRequest $request): JsonResponse
    {
        $this->authService->verifyEmail($request->validated('email'), $request->validated('otp_code'),);

        return $this->successResponse(
            title: 'تمت العملية بنجاح',
            message: 'تم تأكيد البريد الإلكتروني بنجاح'
        );
    }

    public function resetPassword(RequestPasswordResetOtpRequest $request)
    {
        $this->authService->requestPasswordResetOtp($request->validated('email'));

        return $this->successResponse(
            title: 'تمت العملية بنجاح',
            message: 'سيتم إرسال رمز تحقق جديد إليك'
        );
    }

    public function logout(LogoutRequest $request , FcmTokenService $fcmTokenService): JsonResponse
    {
        $user = auth('api')->user();

        if ($user) {
            if ($request->filled('fcm_token')) {
                $fcmTokenService->revokeForUserByToken(
                    user: $user,
                    token: $request->input('fcm_token'),
                    firebaseProject: 'mobile',
                );
            } elseif ($request->filled('device_id')) {
                $fcmTokenService->revokeForUserByDeviceId(
                    user: $user,
                    deviceId: $request->input('device_id'),
                    firebaseProject: 'mobile',
                );
            } else {
                Log::info('Logout without FCM token or device id', [
                    'user_id' => $user->id,
                    'firebase_project' => 'mobile',
                ]);
            }
        }

        auth('api')->logout();

        return $this->successResponse(
            message: "تم تسجيل الخروج من حسابك بنجاح ، شكرا لاستخدامك تطبيق نيرد"
        );
    }
}
