<?php

namespace App\Http\Controllers\V1\Admin;

use App\Enums\FirebaseProject;
use App\Enums\NotificationPlatform;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Auth\LogoutRequest;
use App\Services\Admin\AuthService;
use App\Services\Notifications\FcmTokenService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AuthDashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthService $authService
    )
    {}

    public function login(LoginRequest $request): JsonResponse
    {
        $payLoad = [
            ...$request->validated(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),

            'fcm_platform' => NotificationPlatform::Web->value,
            'firebase_project' => FirebaseProject::Web->value,
        ];

        $data = $this->authService->login($payLoad);

        return $this->dataResponse(
            data: $data,
            title: "تم تسجيل الدخول بنجاح",
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
                    firebaseProject: 'web',
                );
            } elseif ($request->filled('device_id')) {
                $fcmTokenService->revokeForUserByDeviceId(
                    user: $user,
                    deviceId: $request->input('device_id'),
                    firebaseProject: 'web',
                );
            } else {
                Log::info('Logout without FCM token or device id', [
                    'user_id' => $user->id,
                    'firebase_project' => 'web',
                ]);
            }
        }

        auth('api')->logout();

        return $this->successResponse(
            message: "تم تسجيل الخروج من حسابك بنجاح ، شكرا لاستخدامك تطبيق نيرد"
        );
    }
}
