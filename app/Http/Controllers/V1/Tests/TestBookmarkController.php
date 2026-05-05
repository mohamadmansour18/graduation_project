<?php

namespace App\Http\Controllers\V1\Tests;

use App\Http\Controllers\Controller;
use App\Services\Tests\TestBookmarkService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TestBookmarkController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TestBookmarkService $testBookmarkService
    ) {}

    public function bookmark(int $testId): JsonResponse
    {
        $result = $this->testBookmarkService->bookmark(
            testId: $testId,
            userId: Auth::id()
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم حفظ الاختبار بنجاح'
        );
    }

    public function unbookmark(int $testId): JsonResponse
    {
        $result = $this->testBookmarkService->unbookmark(
            testId: $testId,
            userId: Auth::id()
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم إزالة الاختبار من المحفوظات بنجاح'
        );
    }
}
