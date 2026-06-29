<?php

namespace App\Repositories\StudyPlans;

use App\Models\StudyPlan;
use App\Models\StudyPlanSubject;
use App\Models\StudySubject;

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
}
