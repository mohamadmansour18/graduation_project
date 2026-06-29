<?php

namespace App\Http\Controllers\V1\StudyPlans;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyPlans\ListStudyPlansRequest;
use App\Http\Requests\StudyPlans\StoreStudyPlanRequest;
use App\Http\Requests\StudyPlans\UpdateStudyPlanRequest;
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

    public function showDetails(int $studyPlanId, StudyPlanService $studyPlanService): JsonResponse
    {
        $result = $studyPlanService->getPlanDetails(
            userId: request()->user()->id,
            studyPlanId: $studyPlanId
        );

        return $this->dataResponse($result);
    }

    public function showTasks(int $studyPlanId, StudyPlanService $studyPlanService): JsonResponse
    {
        $result = $studyPlanService->getPlanTasks(
            userId: request()->user()->id,
            studyPlanId: $studyPlanId
        );

        return $this->dataResponse($result);
    }

    public function update(int $studyPlanId, UpdateStudyPlanRequest $request,): JsonResponse
    {
        $this->studyPlanService->updateStudyPlan(
            userId: $request->user()->id,
            studyPlanId: $studyPlanId,
            data: $request->validated()
        );

        return $this->successResponse(
            message: 'تم تعديل الخطة الدراسية بنجاح'
        );
    }

    public function destroy(int $studyPlanId,): JsonResponse
    {
        $this->studyPlanService->deleteStudyPlan(
            userId: request()->user()->id,
            studyPlanId: $studyPlanId
        );

        return $this->successResponse(
            message: 'تم حذف الخطة الدراسية بنجاح'
        );
    }
}
