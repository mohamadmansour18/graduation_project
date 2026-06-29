<?php

namespace App\Http\Controllers\V1\StudyPlans;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyPlans\StoreStudyTaskRequest;
use App\Http\Requests\StudyPlans\UpdateStudyTaskRequest;
use App\Http\Resources\StudyTaskEditDetailsResource;
use App\Services\StudyPlans\StudyTaskService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class StudyTaskController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly StudyTaskService $studyTaskService
    )
    {}
    public function store(int $studyPlanId, StoreStudyTaskRequest $request,): JsonResponse
    {
        $message = $this->studyTaskService->createTask(
            userId: $request->user()->id,
            studyPlanId: $studyPlanId,
            data: $request->validated()
        );

        return $this->successResponse(
            message: $message
        );
    }

    public function update(int $studyPlanId, int $taskId, UpdateStudyTaskRequest $request): JsonResponse
    {
        $message = $this->studyTaskService->updateTask(
            userId: $request->user()->id,
            studyPlanId: $studyPlanId,
            taskId: $taskId,
            data: $request->validated()
        );

        return $this->successResponse(
            message: $message
        );
    }

    public function destroy(int $studyPlanId, int $taskId,): JsonResponse
    {
        $this->studyTaskService->deleteTask(
            userId: request()->user()->id,
            studyPlanId: $studyPlanId,
            taskId: $taskId
        );

        return $this->successResponse(
            message: 'تم حذف المهمة بنجاح'
        );
    }

    public function details(int $studyPlanId, int $taskId): JsonResponse
    {
        $task = $this->studyTaskService->getTaskDetailsForEditing(
            userId: request()->user()->id,
            studyPlanId: $studyPlanId,
            taskId: $taskId
        );

        return $this->dataResponse(
            data: new StudyTaskEditDetailsResource($task),
            title: '! تم جلب بيانات المهمة بنجاح'
        );
    }
}
