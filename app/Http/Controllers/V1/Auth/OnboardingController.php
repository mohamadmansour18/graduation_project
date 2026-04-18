<?php

namespace App\Http\Controllers\V1\Auth;

use App\Exceptions\Api\OnboardingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetOnboardingPreviewRequest;
use App\Http\Requests\SaveDiscoverySourceRequest;
use App\Http\Requests\SaveEducationLevelRequest;
use App\Http\Requests\SaveGraduateAcademicProfileRequest;
use App\Http\Requests\SaveSchoolStageRequest;
use App\Http\Requests\SaveUniversityProfileRequest;
use App\Http\Requests\SaveUserInterestsRequest;
use App\Services\Auth\OnboardingService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class OnboardingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OnboardingService $onboardingService
    ){}

    public function saveDiscoverySource(SaveDiscoverySourceRequest $request):JsonResponse
    {
        $data = $this->onboardingService->saveDiscoverySource($request->validated('email') , $request->validated('discovery_source'));

        return $this->dataResponse(
            data: $data,
            title: 'تم حفظ طريقة معرفة التطبيق بنجاح'
        );
    }

    public function saveEducationLevel(SaveEducationLevelRequest $request):JsonResponse
    {
        $data = $this->onboardingService->saveEducationLevel($request->validated('email') , $request->validated('governorate') , $request->validated('education_level'));

        return $this->dataResponse(
            data: $data,
            title: 'تم حفظ المحافظة والمستوى الدراسي بنجاح'
        );
    }

    public function saveSchoolStage(SaveSchoolStageRequest $request): JsonResponse
    {
        $data = $this->onboardingService->saveSchoolStage($request->validated('email'), $request->validated('school_stage'),);

        return $this->dataResponse(
            data: $data,
            title: 'تم حفظ المرحلة الدراسية بنجاح'
        );
    }

    public function saveUniversityProfile(SaveUniversityProfileRequest $request): JsonResponse
    {
        $data = $this->onboardingService->saveUniversityProfile($request->validated('email'), $request->validated('university_name'), $request->validated('department'), $request->validated('university_year'),);

        return $this->dataResponse(
            data: $data,
            title: 'تم حفظ بيانات الجامعة بنجاح'
        );
    }

    public function saveGraduateAcademicProfile(SaveGraduateAcademicProfileRequest $request): JsonResponse
    {
        $data = $this->onboardingService->saveGraduateAcademicProfile($request->validated('email'), $request->validated('university_name'), $request->validated('department'), $request->file('certificate_image'), $request->file('identity_image'),);

        return $this->dataResponse(
            data: $data,
            title: 'تم حفظ البيانات الأكاديمية بنجاح'
        );
    }

    public function saveUserInterests(SaveUserInterestsRequest $request): JsonResponse
    {
        $data = $this->onboardingService->saveUserInterests($request->validated('email'), $request->validated('interest_ids'),);

        return $this->dataResponse(
            data: $data,
            title: 'تم حفظ الاهتمامات العلمية بنجاح'
        );
    }

    public function getInterestCategoriesWithInterests(): JsonResponse
    {
        $data = $this->onboardingService->getInterestCategoriesWithInterests();

        return $this->dataResponse(
            data: $data,
            title: 'تم جلب الاهتمامات العلمية بنجاح'
        );
    }


    public function getOnboardingProgressPreview(GetOnboardingPreviewRequest $request): JsonResponse
    {
        $data = $this->onboardingService->getOnboardingProgressPreview($request->validated('email'));

        return $this->dataResponse(
            data: $data,
            title: 'تم جلب بيانات التهيئة بنجاح'
        );
    }
}
