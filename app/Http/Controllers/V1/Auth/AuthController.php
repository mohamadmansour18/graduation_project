<?php

namespace App\Http\Controllers\V1\Auth;

use App\Exceptions\Jwt\RefreshTokenExpiredException;
use App\Exceptions\Jwt\TokenMissingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\Reset_Password\RequestPasswordResetOtpRequest;
use App\Http\Requests\VerifyEmailOtpRequest;
use App\Services\Auth\AuthService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
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

        $this->ensureRefreshTtlStillValid($token);

        $newToken = JWTAuth::setToken($token)->refresh();

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

    private function ensureRefreshTtlStillValid(string $token): void
    {
        $payload = JWTAuth::setToken($token)->getPayload();

        $issuedAt = $payload->get('iat');

        $refreshTtlMinutes = config('jwt.refresh_ttl');

        if ($refreshTtlMinutes === null) {
            return;
        }

        $refreshExpiresAt = $issuedAt + ((int) $refreshTtlMinutes * 60);

        if (now()->timestamp > $refreshExpiresAt) {
            throw new RefreshTokenExpiredException();
        }
    }
}
