<?php

namespace App\Http\Controllers\V1\Tests;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tests\FilterTestsRequest;
use App\Http\Resources\Tests\FilteredTestResource;
use App\Services\Tests\TestFilterService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class TestFilterController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TestFilterService $testFilterService
    )
    {}

    public function filter(FilterTestsRequest $request): JsonResponse
    {
        $paginator = $this->testFilterService->filter(
            filters: $request->validated(),
            userId: (int)auth()->id()
        );

        return $this->cursorPaginatedResponse(
            paginator: $paginator,
            data: FilteredTestResource::collection($paginator->items()),
            title: '! تم جلب الاختبارات بنجاح'
        );
    }

}
