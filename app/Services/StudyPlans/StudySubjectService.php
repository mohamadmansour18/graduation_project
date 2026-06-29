<?php

namespace App\Services\StudyPlans;

use App\Exceptions\Api\StudyPlanException;
use App\Repositories\StudyPlans\StudySubjectRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

class StudySubjectService
{
    public function __construct(
        private readonly StudySubjectRepository $studySubjectRepository,
    ) {}

    public function createSubject(int $userId, string $name): void
    {
        if ($this->studySubjectRepository->countUserSubjects($userId) >= 50) {
            throw StudyPlanException::maxSubjectsReached();
        }

        if ($this->studySubjectRepository->existsByNameForUser($userId, $name)) {
            throw StudyPlanException::subjectAlreadyExists();
        }

        try {
            $this->studySubjectRepository->create($userId, $name);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                throw StudyPlanException::subjectAlreadyExists();
            }

            throw $exception;
        }
    }

    public function deleteSubject(int $userId, int $subjectId): void
    {
        $subject = $this->studySubjectRepository->findForUser($userId, $subjectId);

        if (! $subject) {
            throw StudyPlanException::subjectNotFound();
        }

        $this->studySubjectRepository->delete($subject);
    }

    public function getUserSubjects(int $userId): Collection
    {
        return $this->studySubjectRepository->getUserSubjects($userId);
    }

}
