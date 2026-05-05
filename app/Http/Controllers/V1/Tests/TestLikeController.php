<?php

namespace App\Http\Controllers\V1\Tests;

use App\Http\Controllers\Controller;
use App\Services\Tests\TestLikeService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TestLikeController extends Controller
{
        use ApiResponse;

    public function __construct(
        private readonly TestLikeService $testLikeService
    ) {}

    public function like(int $testId): JsonResponse
    {
        $result = $this->testLikeService->like(
            testId: $testId,
            userId: Auth::id()
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم تسجيل الإعجاب بنجاح'
        );
    }

    public function unlike(int $testId): JsonResponse
    {
        $result = $this->testLikeService->unlike(
            testId: $testId,
            userId: Auth::id()
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم إزالة الإعجاب بنجاح'
        );
    }
}
