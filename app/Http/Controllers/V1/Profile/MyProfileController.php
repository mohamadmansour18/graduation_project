<?php

namespace App\Http\Controllers\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateAcademicInformationRequest;
use App\Http\Requests\Profile\UpdatePersonalInformationRequest;
use App\Http\Requests\Profile\UpdateScientificInterestsRequest;
use App\Http\Resources\MyBasicProfileResource;
use App\Services\Profile\MyProfileService;
use App\Trait\ApiResponse;
use Auth;
use Illuminate\Http\JsonResponse;

class MyProfileController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly MyProfileService $myProfileService
    ){}

    public function myBasicInfo(int $userId): JsonResponse
    {
        $profile = $this->myProfileService->getMyBasicInfo(
            userId: $userId,
            viewerId: Auth::id()
        );

        return $this->dataResponse(
            data: new MyBasicProfileResource($profile),
            title: '! تم جلب بيانات الملف الشخصي بنجاح'
        );
    }

    public function updatePersonalInformation(UpdatePersonalInformationRequest $request): JsonResponse
    {
        $this->myProfileService->updatePersonalInformation(
            userId: $request->user()->id,
            data: $request->validated()
        );

        return $this->successResponse(
            title: '! تم تعديل المعلومات الشخصية بنجاح'
        );
    }

    public function updateAcademicInformation(UpdateAcademicInformationRequest $request): JsonResponse
    {
        $this->myProfileService->updateAcademicInformation(
            userId: $request->user()->id,
            data: $request->validated(),
            certificateImage: $request->file('certificate_image'),
            identityImage: $request->file('identity_image')
        );

        return $this->successResponse(
            title: '! تم تعديل المعلومات الدراسية بنجاح'
        );
    }

    public function updateScientificInterests(UpdateScientificInterestsRequest $request): JsonResponse
    {
        $this->myProfileService->updateScientificInterests(
            userId: $request->user()->id,
            interestIds: $request->validated('interest_ids')
        );

        return $this->successResponse(
            title: '! تم تعديل الاهتمامات العلمية بنجاح'
        );
    }
}
