<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Services\Admin\AuthService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

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
        ];

        $data = $this->authService->login($payLoad);

        return $this->dataResponse(
            data: $data,
            title: "تم تسجيل الدخول بنجاح",
        );
    }
}
