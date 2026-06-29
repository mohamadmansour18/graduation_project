<?php

namespace App\Repositories\StudyPlans;

use App\Models\StudyTaskSubtask;

class StudyTaskSubtaskRepository
{
    public function findSubtaskForUserForUpdate(int $userId, int $studyPlanId, int $taskId, int $subtaskId): ?StudyTaskSubtask
    {
        return StudyTaskSubtask::query()
            ->select('study_task_subtask.*')
            ->join('study_task', 'study_task.id', '=', 'study_task_subtask.study_task_id')
            ->join('study_plan', 'study_plan.id', '=', 'study_task.study_plan_id')
            ->where('study_task_subtask.id', $subtaskId)
            ->where('study_task_subtask.study_task_id', $taskId)
            ->where('study_task.id', $taskId)
            ->where('study_task.study_plan_id', $studyPlanId)
            ->where('study_plan.id', $studyPlanId)
            ->where('study_plan.user_id', $userId)
            ->lockForUpdate()
            ->first();
    }

    public function markSubtaskAsCompleted(StudyTaskSubtask $subtask): void
    {
        $subtask->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    public function markSubtaskAsUncompleted(StudyTaskSubtask $subtask): void
    {
        $subtask->update([
            'is_completed' => false,
            'completed_at' => null,
        ]);
    }
}
