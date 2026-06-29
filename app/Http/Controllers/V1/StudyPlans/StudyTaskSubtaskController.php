<?php

namespace App\Http\Controllers\V1\StudyPlans;

use App\Http\Controllers\Controller;
use App\Services\StudyPlans\StudyTaskSubtaskService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class StudyTaskSubtaskController extends Controller
{
    use APIResponse;

    public function __construct(
        private readonly StudyTaskSubtaskService $studyTaskSubtaskService
    )
    {}

    public function completeSubtask(int $studyPlanId, int $taskId, int $subtaskId,): JsonResponse
    {
        $this->studyTaskSubtaskService->completeSubtask(
            userId: request()->user()->id,
            studyPlanId: $studyPlanId,
            taskId: $taskId,
            subtaskId: $subtaskId
        );

        return $this->successResponse(
            message: 'تم تعليم المهمة الفرعية كمنجزة بنجاح'
        );
    }

    public function unCompleteSubtask(int $studyPlanId, int $taskId, int $subtaskId,): JsonResponse
    {
        $this->studyTaskSubtaskService->unCompleteSubtask(
            userId: request()->user()->id,
            studyPlanId: $studyPlanId,
            taskId: $taskId,
            subtaskId: $subtaskId
        );

        return $this->successResponse(
            message: 'تم إزالة إنجاز المهمة الفرعية بنجاح'
        );
    }
}
