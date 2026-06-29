<?php

namespace App\Services\StudyPlans;

use App\Events\StudyPlanCreated;
use App\Exceptions\Api\StudyPlanException;
use App\Http\Resources\StudyPlanOverviewResource;
use App\Repositories\StudyPlans\StudyPlanRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudyPlanService
{
    public function __construct(
        private readonly StudyPlanRepository $studyPlanRepository,
    )
    {}

    public function createStudyPlan(int $userId, array $data, bool $isDefaultWasProvided): void
    {
        $subjectIds = array_values($data['subject_ids']);
        $isDefault = (bool) ($data['is_default'] ?? false);

        $allPlansCount = $this->studyPlanRepository->countAllUserPlans($userId);

        if ($allPlansCount === 0 && ! $isDefaultWasProvided) {
            throw StudyPlanException::defaultFlagRequiredForFirstPlan();
        }

        if ($allPlansCount === 0 && ! $isDefault) {
            throw StudyPlanException::firstPlanMustBeDefault();
        }

        $activeOrFuturePlansCount = $this->studyPlanRepository->countActiveOrFuturePlans($userId);

        if ($activeOrFuturePlansCount >= 5) {
            throw StudyPlanException::maxActiveOrFuturePlansReached();
        }

        $ownedSubjectsCount = $this->studyPlanRepository->countUserSubjectsByIds(
            userId: $userId,
            subjectIds: $subjectIds
        );

        if ($ownedSubjectsCount !== count($subjectIds)) {
            throw StudyPlanException::someSubjectsDoNotBelongToUser();
        }

        DB::transaction(function () use ($userId, $data, $subjectIds, $isDefault) {
            if ($isDefault) {
                $this->studyPlanRepository->unsetDefaultPlansForUser($userId);
            }

            $studyPlan = $this->studyPlanRepository->createPlan([
                'user_id' => $userId,
                'title' => $data['title'],
                'emoji' => $data['emoji'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],

                'daily_study_minutes' => ((int) $data['daily_study_hours']) * 60,

                'is_default' => $isDefault,
                'subjects_count' => count($subjectIds),

                'tasks_count' => 0,
                'completed_tasks_count' => 0,
                'missed_tasks_count' => 0,
                'pending_tasks_count' => 0,
            ]);

            $this->studyPlanRepository->attachSubjects(
                studyPlanId: $studyPlan->id,
                subjectIds: $subjectIds
            );

            DB::afterCommit(function () use ($studyPlan) {
                StudyPlanCreated::dispatch(
                    $studyPlan->user_id,
                    $studyPlan->id,
                    $studyPlan->start_date->toDateString()
                );
            });
        });
    }

    public function getUserPlansByTab(int $userId, string $tab): array
    {
        $plans = $this->studyPlanRepository->getUserPlansByTab(
            userId: $userId,
            tab: $tab
        );

        return [
            'plans_count' => $plans->count(),
            'plans' => StudyPlanOverviewResource::collection($plans),
        ];
    }
}
