<?php

namespace App\Http\Controllers\V1\StudyPlans;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyPlans\GetDailyTasksOverviewRequest;
use App\Services\StudyPlans\DailyTaskService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class DailyTaskController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly DailyTaskService $dailyTaskService
    )
    {}

    public function overview(GetDailyTasksOverviewRequest $request,): JsonResponse
    {
        $result = $this->dailyTaskService->getOverview(
            userId: $request->user()->id,
            selectedDate: $request->validated('date'),
            rangeStart: $request->validated('range_start'),
            rangeEnd: $request->validated('range_end'),
        );

        return $this->dataResponse($result);
    }
}
