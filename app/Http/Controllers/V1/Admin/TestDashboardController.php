<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestManagementBoardRequest;
use App\Http\Resources\Admin\TestManagementBoardResource;
use App\Http\Resources\TestManagementDetailsResource;
use App\Services\Admin\TestDashboardService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TestDashboardController extends Controller
{
    use ApiResponse;

    public function managementBoard(TestManagementBoardRequest $request, TestDashboardService $service): JsonResponse
    {
        $result = $service->getManagementBoard(
            date: $request->validated('date') ?? null
        );

        return $this->dataResponse(
            data: new TestManagementBoardResource($result),
            title: '! تم جلب اختبارات لوحة المراجعة بنجاح'
        );
    }

    public function managementTestDetails(int $test, TestDashboardService $service): JsonResponse
    {
        $testDetails = $service->getManagementTestDetails($test);

        return $this->dataResponse(
            data: new TestManagementDetailsResource($testDetails),
            title: '! تم جلب تفاصيل الاختبار بنجاح'
        );
    }

    public function approveManagementTest(int $test, TestDashboardService $service): JsonResponse
    {
        $result = $service->approveManagementTest(
            testId: $test,
            reviewer: Auth::user(),
        );

        return $this->dataResponse(
            data: $result,
            title: '! تمت الموافقة على نشر الاختبار بنجاح'
        );
    }
}
