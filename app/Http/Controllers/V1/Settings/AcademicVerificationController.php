<?php

namespace App\Http\Controllers\V1\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SubmitAcademicVerificationRequest;
use App\Http\Requests\Settings\UpdateCertificateVisibilityRequest;
use App\Services\Settings\AcademicVerificationService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class AcademicVerificationController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly AcademicVerificationService $service
    )
    {}

    public function status(): JsonResponse
    {
        $data = $this->service->getStatus(
            userId: \Auth::id()
        );

        return $this->dataResponse(
            data: $data,
            title: '! تم جلب حالة التوثيق الأكاديمي بنجاح'
        );
    }

    public function submit(SubmitAcademicVerificationRequest $request): JsonResponse
    {
        $this->service->submitRequest(
            userId: $request->user()->id,
            certificateImage: $request->file('certificate_image'),
            identityImage: $request->file('identity_image')
        );

        return $this->successResponse(
            message: 'تم إرسال طلب التوثيق الأكاديمي بنجاح'
        );
    }

    public function updateCertificateVisibility(UpdateCertificateVisibilityRequest $request): JsonResponse
    {
        $this->service->updateCertificateVisibility(
            userId: $request->user()->id,
            showPublicly: $request->boolean('show_certificate_publicly')
        );

        return $this->successResponse(
            message: 'تم تحديث حالة ظهور الشهادة العلمية بنجاح'
        );
    }

    public function cancel(): JsonResponse
    {
        $this->service->cancelRequest(
            userId: \Auth::id()
        );

        return $this->successResponse(
            message: 'تم إلغاء طلب التوثيق الأكاديمي بنجاح'
        );
    }
}
