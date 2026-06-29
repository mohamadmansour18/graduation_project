<?php

namespace App\Http\Controllers\V1\StudyPlans;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudyPlans\StoreStudySubjectRequest;
use App\Services\StudyPlans\StudySubjectService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class StudySubjectController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly StudySubjectService $studySubjectService
    )
    {}

    public function store(StoreStudySubjectRequest $request): JsonResponse
    {
        $this->studySubjectService->createSubject(
            userId: $request->user()->id,
            name: $request->validated('name'),
        );

        return $this->successResponse(
            message: 'تم إنشاء المادة الدراسية بنجاح'
        );
    }

    public function destroy(int $subjectId): JsonResponse
    {
        $this->studySubjectService->deleteSubject(
            userId: request()->user()->id,
            subjectId: $subjectId,
        );

        return $this->successResponse(
            message: 'تم حذف المادة الدراسية بنجاح'
        );
    }

    public function show(): JsonResponse
    {
        $subjects = $this->studySubjectService->getUserSubjects(
            userId: request()->user()->id
        );

        return $this->dataResponse($subjects);
    }
}
