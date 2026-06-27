<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListDashboardSalesRequest;
use App\Http\Resources\DashboardSalesHistoryResource;
use App\Services\Admin\PaidDashboardService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class PaidDashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PaidDashboardService $paidDashboardService
    )
    {}

    public function salesHistory(ListDashboardSalesRequest $request): JsonResponse
    {
        $result = $this->paidDashboardService->getSalesHistory(
            owner: $request->user(),
            filters: $request->validated(),
        );

        return $this->dataResponse(
            data: new DashboardSalesHistoryResource($result),
            title: '! تم جلب سجل المبيعات بنجاح'
        );
    }
}
