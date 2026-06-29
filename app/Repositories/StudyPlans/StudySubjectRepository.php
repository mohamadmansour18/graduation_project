<?php

namespace App\Repositories\StudyPlans;

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
}
