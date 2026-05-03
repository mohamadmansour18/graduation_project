<?php

namespace App\Http\Controllers\V1\Tests;

use App\Enums\TestSearchScope;
use App\Http\Requests\Search\SearchTestsRequest;
use App\Services\Home\TestSearchService;
use App\Trait\ApiResponse;

class LabController
{
    use ApiResponse;

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
}
