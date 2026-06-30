<?php

namespace App\Http\Controllers\V1\Home;

use App\Enums\TestSearchScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchTestsByInterestRequest;
use App\Http\Requests\Search\SearchUsersRequest;
use App\Services\Home\HomeService;
use App\Services\Home\TestSearchService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly HomeService $homeService
    ) {}

    public function getInterests(): JsonResponse
    {
        $categories = $this->homeService->getScientificCategoriesForHome(Auth::id());

        return $this->dataResponse(
            data: $categories ,
            title: 'تم جلب التصنيفات العلمية بنجاح'
        );
    }

    public function topTestCreators(): JsonResponse
    {
        $creators = $this->homeService->getTopTestCreators();

        return $this->dataResponse(
            data: $creators,
            title: 'تم جلب أشهر أصحاب الاختبارات بنجاح'
        );
    }

    public function scientificInterests(): JsonResponse
    {
        $categories = $this->homeService->getScientificInterestsGroupedByCategory();

        return $this->dataResponse(
            data:$categories,
            title: 'تم جلب التصنيفات العلمية بنجاح'
        );
    }

    public function testsByInterest(int $interestId): JsonResponse
    {
        $userId = Auth::id();

        $paginator = $this->homeService->getTestsByInterest($interestId , $userId);

        return $this->paginatedResponse(
            paginator: $paginator,
            title: 'تم جلب اختبارات التصنيف العلمي بنجاح'
        );
    }

    public function searchTests(SearchTestsByInterestRequest $request , TestSearchService $service)
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

    public function searchUsers(SearchUsersRequest $request,): JsonResponse
    {
        $data = $this->homeService->searchUsers(
            viewerUserId: $request->user()->id,
            query: $request->searchQuery(),
            perPage: $request->perPage()
        );

        return $this->dataResponse(
            data: $data,
            title: '! تم جلب نتائج البحث بنجاح'
        );
    }

    public function searchHistory(): JsonResponse
    {
        $data = $this->homeService->getSearchHistory(
            userId: Auth::id()
        );

        return $this->dataResponse(
            data: $data,
            title: '! تم جلب سجل البحث بنجاح'
        );
    }

    public function clearSearchHistory(): JsonResponse
    {
        $this->homeService->clearSearchHistory(
            userId: Auth::id()
        );

        return $this->successResponse(
            message: 'تم حذف سجل البحث بنجاح'
        );
    }

    public function deleteSearchHistoryItem(int $historyId): JsonResponse
    {
        $this->homeService->deleteSearchHistoryItem(
            userId: Auth::id(),
            historyId: $historyId
        );

        return $this->successResponse(
            message: 'تم حذف عنصر سجل البحث بنجاح'
        );
    }
}
