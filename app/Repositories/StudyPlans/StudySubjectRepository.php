<?php

namespace App\Repositories\StudyPlans;

use App\Models\StudyPlan;
use App\Models\StudyPlanSubject;
use App\Models\StudySubject;
use Illuminate\Support\Collection;

class StudySubjectRepository
{
    public function countUserSubjects(int $userId): int
    {
        return StudySubject::query()
            ->where('user_id', $userId)
            ->count();
    }

    public function existsByNameForUser(int $userId, string $name): bool
    {
        return StudySubject::query()
            ->where('user_id', $userId)
            ->where('name', $name)
            ->exists();
    }

    public function create(int $userId, string $name): StudySubject
    {
        return StudySubject::query()->create([
            'user_id' => $userId,
            'name' => $name,
        ]);
    }

    public function findForUser(int $userId, int $subjectId): ?StudySubject
    {
        return StudySubject::query()
            ->where('id', $subjectId)
            ->where('user_id', $userId)
            ->first();
    }

    public function delete(StudySubject $subject): void
    {
        $subject->delete();
    }

    public function getUserSubjects(int $userId): Collection
    {
        return StudySubject::query()
            ->select(['id', 'name'])
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get();
    }

    public function existsPlanForUser(int $userId, int $studyPlanId): bool
    {
        return StudyPlan::query()
            ->where('id', $studyPlanId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function getPlanSubjectsOptions(int $studyPlanId): array
    {
        return StudyPlanSubject::query()
            ->select([
                'study_plan_subject.id',
                'study_subject.name',
            ])
            ->join('study_subject', 'study_subject.id', '=', 'study_plan_subject.study_subject_id')
            ->where('study_plan_subject.study_plan_id', $studyPlanId)
            ->orderBy('study_plan_subject.slot_no')
            ->orderBy('study_plan_subject.id')
            ->get()
            ->map(fn ($subject) => [
                'id' => (int) $subject->id,
                'name' => $subject->name,
            ])
            ->all();
    }
}
