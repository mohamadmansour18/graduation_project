<?php

namespace App\Http\Controllers\V1\StudyPlans;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyPlans\ListStudyPlansRequest;
use App\Http\Requests\StudyPlans\StoreStudyPlanRequest;
use App\Services\StudyPlans\StudyPlanService;
use App\Trait\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class StudyPlanController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly StudyPlanService $studyPlanService
    ){}

    public function store(StoreStudyPlanRequest $request): JsonResponse
    {
        $this->studyPlanService->createStudyPlan(
            userId: $request->user()->id,
            data: $request->validated(),
            isDefaultWasProvided: $request->has('is_default')
        );

        return $this->successResponse(
            message: 'تم إنشاء الخطة الدراسية بنجاح'
        );
    }

    public function show(ListStudyPlansRequest $request): JsonResponse
    {
        $result = $this->studyPlanService->getUserPlansByTab(
            userId: $request->user()->id,
            tab: $request->selectedTab()
        );

        return $this->dataResponse($result);
    }
}
