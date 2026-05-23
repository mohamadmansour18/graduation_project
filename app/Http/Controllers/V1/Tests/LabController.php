<?php

namespace App\Http\Controllers\V1\Tests;

use App\Enums\TestSearchScope;
use App\Http\Requests\Search\SearchTestsRequest;
use App\Http\Requests\Tests\StoreCreateTestRequest;
use App\Services\Home\TestSearchService;
use App\Services\Tests\TestCreationService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class LabController
{
    use ApiResponse;

    public function __construct(
        private readonly TestCreationService $testCreationService
    ) {}

    public function searchTests(SearchTestsRequest $request , TestSearchService $service)
    {
        $filters = \App\DTOs\Search\TestSearchFilters::fromRequest(
            $request->validated(),
            $request->user()->id,
            TestSearchScope::OTHERS->value
        );

        $paginator = $service->search($filters);

        return $this->paginatedResponse(
            paginator: $paginator,
            title: 'تم البحث عن الاختبارات بنجاح'
        );
    }

    ////////////////////////////////////////////////////////////////////////

    public function store(StoreCreateTestRequest $request): JsonResponse
    {
        $this->testCreationService->create(
            user: $request->user(),
            data: $request->validated()
        );

        return $this->successResponse(
            title: '! تم إنشاء الاختبار بنجاح',
            message: 'تم حفظ الاختبار الاختبار الخاص بك يمكنك مشاهدته في ملفك الشخصي'
        );
    }
}
