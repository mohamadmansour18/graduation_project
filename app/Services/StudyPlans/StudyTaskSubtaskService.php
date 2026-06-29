<?php

namespace App\Services\StudyPlans;

use App\Exceptions\Api\StudyPlanException;
use App\Repositories\StudyPlans\StudyTaskSubtaskRepository;
use Illuminate\Support\Facades\DB;

class StudyTaskSubtaskService
{
    public function __construct(
        private readonly StudyTaskSubtaskRepository $studyTaskSubtaskRepository
    )
    {}

    public function completeSubtask(int $userId, int $studyPlanId, int $taskId, int $subtaskId): void
    {
        DB::transaction(function () use ($userId, $studyPlanId, $taskId, $subtaskId) {
            $subtask = $this->studyTaskSubtaskRepository->findSubtaskForUserForUpdate(
                userId: $userId,
                studyPlanId: $studyPlanId,
                taskId: $taskId,
                subtaskId: $subtaskId
            );

            if (! $subtask) {
                throw StudyPlanException::subtaskNotFound();
            }

            if ((bool) $subtask->is_completed) {
                return;
            }

            $this->studyTaskSubtaskRepository->markSubtaskAsCompleted($subtask);
        });
    }

    public function unCompleteSubtask(int $userId, int $studyPlanId, int $taskId, int $subtaskId): void
    {
        DB::transaction(function () use ($userId, $studyPlanId, $taskId, $subtaskId) {
            $subtask = $this->studyTaskSubtaskRepository->findSubtaskForUserForUpdate(
                userId: $userId,
                studyPlanId: $studyPlanId,
                taskId: $taskId,
                subtaskId: $subtaskId
            );

            if (! $subtask) {
                throw StudyPlanException::subtaskNotFound();
            }

            if (! (bool) $subtask->is_completed) {
                return;
            }

            $this->studyTaskSubtaskRepository->markSubtaskAsUncompleted($subtask);
        });
    }
}
