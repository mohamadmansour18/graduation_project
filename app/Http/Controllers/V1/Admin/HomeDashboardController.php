<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GetYearlyTestActivityRequest;
use App\Http\Resources\Admin\AdminFinancialDashboardResource;
use App\Http\Resources\Admin\UsersAndLibraryStatsResource;
use App\Http\Resources\Admin\YearlyTestActivityMonthResource;
use App\Services\Admin\AdminFinancialStatsService;
use App\Services\Admin\HomeService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class HomeDashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly HomeService $service
    )
    {}

    public function yearlyTestActivity(GetYearlyTestActivityRequest $request): JsonResponse
    {
        $result = $this->service->getYearlyTestActivity(
            year: $request->validatedYear()
        );

        return $this->dataResponse([
            'year' => $result['year'],
            'months' => YearlyTestActivityMonthResource::collection($result['months']),
        ]);
    }

    public function financialStats(GetYearlyTestActivityRequest $request, AdminFinancialStatsService $adminFinancialStatsService): JsonResponse
    {
        $result = $adminFinancialStatsService->getFinancialStats(
            year: $request->validatedYear()
        );

        return $this->dataResponse(
            data: new AdminFinancialDashboardResource($result),
            title: '! تم جلب الإحصائيات المالية بنجاح'
        );
    }

    public function usersAndLibraryStats(GetYearlyTestActivityRequest $request,): JsonResponse
    {
        $result = $this->service->getStats(
            year: $request->validatedYear()
        );

        return $this->dataResponse(
            data: new UsersAndLibraryStatsResource($result),
            title: '! تم جلب إحصائيات المستخدمين والمكتبة بنجاح'
        );
    }
}
