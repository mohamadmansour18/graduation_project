<?php

namespace App\Repositories\StudyPlans;

use App\Models\StudyPlan;
use App\Models\StudyPlanSubject;
use App\Models\StudySubject;
use App\Models\StudyTask;
use App\Models\StudyTaskOccurrence;
use Illuminate\Support\Facades\DB;

class StudyPlanRepository
{
    public function countAllUserPlans(int $userId): int
    {
        return StudyPlan::query()
            ->where('user_id', $userId)
            ->count();
    }

    public function countActiveOrFuturePlans(int $userId): int
    {
        return StudyPlan::query()
            ->where('user_id', $userId)
            ->whereDate('end_date', '>=', today())
            ->count();
    }

    public function countUserSubjectsByIds(int $userId, array $subjectIds): int
    {
        return StudySubject::query()
            ->where('user_id', $userId)
            ->whereIn('id', $subjectIds)
            ->count();
    }

    public function unsetDefaultPlansForUser(int $userId): void
    {
        StudyPlan::query()
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    public function createPlan(array $data): StudyPlan
    {
        return StudyPlan::query()->create($data);
    }

    public function attachSubjects(int $studyPlanId, array $subjectIds): void
    {
        $rows = collect($subjectIds)
            ->values()
            ->map(fn (int $subjectId, int $index) => [
                'study_plan_id' => $studyPlanId,
                'study_subject_id' => $subjectId,
                'slot_no' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        StudyPlanSubject::query()->insert($rows);
    }

    public function getUserPlansByTab(int $userId, string $tab): array|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_StudyPlan_C
    {
        $today = today()->toDateString();

        return StudyPlan::query()
            ->select([
                'id',
                'user_id',
                'title',
                'emoji',
                'start_date',
                'end_date',
                'daily_study_minutes',
                'is_default',
                'subjects_count',
                'tasks_count',
                'completed_tasks_count',
                'missed_tasks_count',
                'pending_tasks_count',
            ])
            ->where('user_id', $userId)
            ->when($tab === 'current', function ($query) use ($today) {
                $query->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today);
            })
            ->when($tab === 'expired', function ($query) use ($today) {
                $query->whereDate('end_date', '<', $today);
            })
            ->when($tab === 'future', function ($query) use ($today) {
                $query->whereDate('start_date', '>', $today);
            })
            ->orderByDesc('is_default')
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();
    }

    public function findPlanDetailsForUser(int $userId, int $studyPlanId): ?StudyPlan
    {
        return StudyPlan::query()
            ->select([
                'id',
                'user_id',
                'title',
                'emoji',
                'start_date',
                'end_date',
                'daily_study_minutes',
                'is_default',
                'subjects_count',
                'tasks_count',
                'completed_tasks_count',
                'missed_tasks_count',
                'pending_tasks_count',
            ])
            ->where('id', $studyPlanId)
            ->where('user_id', $userId)
            ->with([
                'studyPlanSubjects:id,study_plan_id,study_subject_id,slot_no',
                'studyPlanSubjects.studySubject:id,name',
            ])
            ->first();
    }

    public function existsForUser(int $userId, int $studyPlanId): bool
    {
        return StudyPlan::query()
            ->where('id', $studyPlanId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function getPlanTaskOccurrencesGroupedData(int $studyPlanId): array|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_StudyTaskOccurrence_C
    {
        $subtaskCounts = DB::table('study_task_subtask')
            ->select([
                'study_task_id',
                DB::raw('COUNT(*) as subtasks_total_count'),
                DB::raw('SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_subtasks_count'),
            ])
            ->groupBy('study_task_id');

        return StudyTaskOccurrence::query()
            ->from('study_task_occurrence as occurrence')
            ->join('study_task as task', 'task.id', '=', 'occurrence.study_task_id')
            ->leftJoinSub($subtaskCounts, 'subtask_counts', function ($join) {
                $join->on('subtask_counts.study_task_id', '=', 'task.id');
            })
            ->where('occurrence.study_plan_id', $studyPlanId)
            ->orderBy('occurrence.occurrence_date')
            ->orderBy('occurrence.scheduled_start_time')
            ->orderBy('occurrence.id')
            ->get([
                'occurrence.id as occurrence_id',
                'occurrence.occurrence_date',
                'occurrence.scheduled_start_time',
                'occurrence.scheduled_end_time',
                'occurrence.duration_second',

                'task.id',
                'task.title',
                'task.status',
                'task.priority',

                DB::raw('COALESCE(subtask_counts.subtasks_total_count, 0) as subtasks_total_count'),
                DB::raw('COALESCE(subtask_counts.completed_subtasks_count, 0) as completed_subtasks_count'),
            ]);
    }

    public function findForUserForUpdate(int $userId, int $studyPlanId): ?StudyPlan
    {
        return StudyPlan::query()
            ->where('id', $studyPlanId)
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();
    }

    public function countUserPlansForUpdate(int $userId): int
    {
        return StudyPlan::query()
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->count();
    }

    public function updatePlan(StudyPlan $studyPlan, array $data): void
    {
        $studyPlan->update($data);
    }

    public function getPlanSubjects(int $studyPlanId): array|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_StudyPlanSubject_C
    {
        return StudyPlanSubject::query()
            ->select(['id', 'study_plan_id', 'study_subject_id'])
            ->where('study_plan_id', $studyPlanId)
            ->get();
    }

    public function hasTasksForPlanSubjectIds(array $planSubjectIds): bool
    {
        if (empty($planSubjectIds)) {
            return false;
        }

        return StudyTask::query()
            ->whereIn('study_plan_subject_id', $planSubjectIds)
            ->exists();
    }

    public function syncPlanSubjects(int $studyPlanId, array $subjectIds): void
    {
        StudyPlanSubject::query()
            ->where('study_plan_id', $studyPlanId)
            ->delete();

        $rows = collect($subjectIds)
            ->values()
            ->map(fn (int $subjectId, int $index) => [
                'study_plan_id' => $studyPlanId,
                'study_subject_id' => $subjectId,
                'slot_no' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        StudyPlanSubject::query()->insert($rows);
    }

    public function hasTasksOutsideDateRange(int $studyPlanId, string $newStartDate, string $newEndDate): bool
    {
        return StudyTask::query()
            ->where('study_plan_id', $studyPlanId)
            ->where(function ($query) use ($newStartDate, $newEndDate) {
                $query->whereDate('start_date', '<', $newStartDate)
                    ->orWhereDate('end_date', '>', $newEndDate);
            })
            ->exists();
    }

    public function maxDailyScheduledSeconds(int $studyPlanId): int
    {
        $row = StudyTaskOccurrence::query()
            ->selectRaw('SUM(duration_second) as total_seconds')
            ->where('study_plan_id', $studyPlanId)
            ->groupBy('occurrence_date')
            ->orderByDesc('total_seconds')
            ->first();

        return (int) ($row?->total_seconds ?? 0);
    }

    public function findReplacementDefaultPlan(int $userId, int $excludedStudyPlanId): ?StudyPlan
    {
        $today = today()->toDateString();

        return StudyPlan::query()
            ->where('user_id', $userId)
            ->where('id', '!=', $excludedStudyPlanId)
            ->orderByRaw('CASE WHEN end_date >= ? THEN 0 ELSE 1 END', [$today])
            ->orderBy('start_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    public function forceDeletePlan(StudyPlan $studyPlan): void
    {
        if (method_exists($studyPlan, 'forceDelete')) {
            $studyPlan->forceDelete();
            return;
        }

        $studyPlan->delete();
    }
}
