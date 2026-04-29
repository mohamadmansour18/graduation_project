<?php

namespace App\Http\Controllers\V1\Home;

use App\Http\Controllers\Controller;
use App\Services\Home\HomeService;
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
}
