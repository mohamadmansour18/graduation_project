<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestManagementBoardRequest;
use App\Http\Resources\Admin\TestManagementBoardResource;
use App\Services\Admin\TestDashboardService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

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
}
