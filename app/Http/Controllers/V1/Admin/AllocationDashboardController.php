<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScientificInterestCategoryRequest;
use App\Http\Requests\Admin\StoreScientificInterestRequest;
use App\Http\Requests\Admin\UpdateScientificInterestCategoryRequest;
use App\Http\Requests\Admin\UpdateScientificInterestRequest;
use App\Models\Interest;
use App\Services\Admin\AllocationDashboardService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class AllocationDashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AllocationDashboardService $service
    )
    {}

    public function ownerStatistics(): JsonResponse
    {
        $result = $this->service->getOwnerStatistics();

        return $this->dataResponse(
            data: $result,
            title: '! تم جلب معلومات المالك الإحصائية بنجاح'
        );
    }

    public function scientificInterests(): JsonResponse
    {
        $result = $this->service->getScientificInterestsGrouped();

        return $this->dataResponse(
            data: $result,
            title: '! تم جلب التصنيفات العلمية بنجاح'
        );
    }

    public function scientificInterestCategories(): JsonResponse
    {
        $result = $this->service->getScientificInterestCategories();

        return $this->dataResponse(
            data: $result,
            title: '! تم جلب عناوين التصنيفات العلمية بنجاح'
        );
    }

    public function storeScientificInterest(StoreScientificInterestRequest $request): JsonResponse
    {
        $this->service->createScientificInterest(
            ownerId: $request->user()->id,
            data: $request->validated(),
            icon: $request->file('icon'),);

        return $this->successResponse(
            title: '! تم إضافة التصنيف العلمي بنجاح',
            message: 'تم حفظ التصنيف العلمي داخل النظام'
        );
    }

    public function updateScientificInterest(UpdateScientificInterestRequest $request, int $interestId): JsonResponse
    {
        $this->service->updateScientificInterest(
            ownerId: $request->user()->id,
            interestId: $interestId,
            data: $request->validated(),
            icon: $request->file('icon'),
        );

        return $this->successResponse(
            title: '! تم تعديل التصنيف العلمي بنجاح',
            message: 'تم حفظ التعديلات بنجاح'
        );
    }

    public function deleteScientificInterest(int $interestId): JsonResponse
    {
        $this->service->deleteScientificInterest(
            ownerId: request()->user()->id,
            interestId: $interestId,
        );

        return $this->successResponse(
            title: '! تم حذف التصنيف العلمي بنجاح',
            message: 'تم حذف التصنيف العلمي من النظام'
        );
    }

    public function storeScientificInterestCategory(StoreScientificInterestCategoryRequest $request): JsonResponse
    {
        $this->service->createScientificInterestCategory(
            ownerId: $request->user()->id,
            data: $request->validated(),
        );

        return $this->successResponse(
            title: '! تم إضافة عنوان التصنيف العلمي بنجاح',
            message: 'تم حفظ عنوان التصنيف العلمي داخل النظام'
        );
    }

    public function updateScientificInterestCategory(UpdateScientificInterestCategoryRequest $request, int $categoryId,): JsonResponse
    {
        $this->service->updateScientificInterestCategory(
            ownerId: $request->user()->id,
            categoryId: $categoryId,
            data: $request->validated(),
        );

        return $this->successResponse(
            title: '! تم تعديل عنوان التصنيف العلمي بنجاح',
            message: 'تم حفظ التعديلات بنجاح'
        );
    }

    public function deleteScientificInterestCategory(int $categoryId,): JsonResponse
    {
        $this->service->deleteScientificInterestCategory(
            ownerId: request()->user()->id,
            categoryId: $categoryId,
        );

        return $this->successResponse(
            title: '! تم حذف عنوان التصنيف العلمي بنجاح',
            message: 'تم حذف عنوان التصنيف العلمي من النظام'
        );
    }
}
