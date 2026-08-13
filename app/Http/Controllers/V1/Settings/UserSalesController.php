<?php

namespace App\Http\Controllers\V1\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\GetMyPurchasedTestsRequest;
use App\Http\Requests\Settings\GetUserSoldTestsRequest;
use App\Http\Resources\Settings\MyPurchasedTestResource;
use App\Services\Settings\UserSalesService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserSalesController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserSalesService $service,
    )
    {}

    public function myPurchasesTest(GetMyPurchasedTestsRequest $request): JsonResponse
    {
        $tests = $this->service->getMyPurchasedTests(
            buyerUserId: (int) $request->user()->id,
            tab: $request->tab(),
        );

        return $this->dataResponse(
            data: MyPurchasedTestResource::collection($tests),
            title: '! تم جلب الاختبارات المشتراة بنجاح',
        );
    }

    public function soldTests(GetUserSoldTestsRequest $request,): JsonResponse
    {
        $data = $this->service->getSoldTests(
            userId: 21,
            tab: $request->tab()
        );

        return $this->dataResponse(
            data: $data,
            title: '! تم جلب الاختبارات المباعة بنجاح'
        );
    }
}
