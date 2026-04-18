<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\Auth\AuthService;
use App\Trait\ApiResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuthService $authService
    ){}

    public function register(RegisterRequest $request)
    {
        $data = $this->authService->register($request->validated());

        return $this->dataResponse(
            data: $data,
            title: "تم انشاء حساب المستخدم بنجاح",
            statusCode: 201,
        );
    }

    public function login(LoginRequest $request)
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
        $newToken = JWTAuth::parseToken()->refresh();

        return $this->dataResponse([
            'newToken' => $newToken,
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }
}
