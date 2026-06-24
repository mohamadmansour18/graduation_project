<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeleteManagementTestRequest;
use App\Http\Requests\Admin\ListDashboardLibraryMaterialsRequest;
use App\Http\Requests\Admin\RequestTestRevisionsRequest;
use App\Http\Requests\Admin\TestManagementBoardRequest;
use App\Http\Requests\Admin\TestManagementReportsRequest;
use App\Http\Requests\Admin\TestManagementReviewsRequest;
use App\Http\Requests\Library\SearchLibraryMaterialRequest;
use App\Http\Resources\Admin\TestManagementBoardResource;
use App\Http\Resources\DashboardLibraryMaterialDetailsResource;
use App\Http\Resources\TestDashboardContentResource;
use App\Http\Resources\TestManagementDetailsResource;
use App\Http\Resources\TestManagementReportsResource;
use App\Http\Resources\TestManagementReviewsResource;
use App\Http\Resources\TestManagementStatusHistoryResource;
use App\Http\Resources\Tests\TestContentResource;
use App\Services\Admin\TestAiEvaluation\TestAiEvaluationService;
use App\Services\Admin\TestDashboardService;
use App\Services\LibraryMaterial\LibraryMaterialService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TestDashboardController extends Controller
{
    use ApiResponse;

    public function managementBoard(TestManagementBoardRequest $request, TestDashboardService $service): JsonResponse
    {
        $result = $service->getManagementBoard(
            date: $request->validated('date') ?? null
        );

        return $this->dataResponse(
            data: new TestManagementBoardResource($result),
            title: '! تم جلب اختبارات لوحة المراجعة بنجاح'
        );
    }

    public function managementTestDetails(int $test, TestDashboardService $service): JsonResponse
    {
        $testDetails = $service->getManagementTestDetails($test);

        return $this->dataResponse(
            data: new TestManagementDetailsResource($testDetails),
            title: '! تم جلب تفاصيل الاختبار بنجاح'
        );
    }

    public function approveManagementTest(int $test, TestDashboardService $service): JsonResponse
    {
        $result = $service->approveManagementTest(
            testId: $test,
            reviewer: Auth::user(),
        );

        return $this->dataResponse(
            data: $result,
            title: '! تمت الموافقة على نشر الاختبار بنجاح'
        );
    }

    public function deleteManagementTest(DeleteManagementTestRequest $request, int $test, TestDashboardService $service): JsonResponse
    {
        $result = $service->deleteManagementTest(
            testId: $test,
            reviewer: $request->user(),
            deletionReason: $request->validated('deletion_reason')
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم حذف الاختبار بنجاح'
        );
    }

    public function requestManagementTestRevisions(RequestTestRevisionsRequest $request, int $test, TestDashboardService $service): JsonResponse
    {
        $result = $service->requestManagementTestRevisions(
            testId: $test,
            reviewer: $request->user(),
            revisions: $request->validated('revisions')
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم إرسال طلب التعديلات بنجاح'
        );
    }

    public function content(int $testId , TestDashboardService $service): JsonResponse
    {
        $data = $service->getQuestions(
            testId: $testId,
        );

        return $this->dataResponse(
            data: new TestDashboardContentResource($data),
            title: '! تم جلب محتوى الاختبار بنجاح'
        );
    }

    public function questionsSamples(int $testId , TestDashboardService $service)
    {
        $data = $service->getQuestionsSamples(
            testId: $testId,
        );

        return $this->dataResponse(
            data: new TestDashboardContentResource($data),
            title: '! تم جلب محتوى الاختبار بنجاح'
        );
    }

    public function managementTestReviews(TestManagementReviewsRequest $request, int $test, TestDashboardService $service): JsonResponse
    {
        $validated = $request->validated();

        $result = $service->getManagementTestReviews(
            testId: $test,
            rating: $validated['rating'] ?? null,
            perPage: $validated['per_page'] ?? 10
        );

        return $this->dataResponse(
            data: new TestManagementReviewsResource($result),
            title: '! تم جلب بيانات مراجعات الاختبار بنجاح'
        );
    }

    public function deleteManagementTestReview(int $review, TestDashboardService $service): JsonResponse
    {
        $service->deleteManagementTestReview(
            reviewId: $review,
            actor: Auth::user()
        );

        return $this->successResponse(
            title: '! تم حذف التعليق بنجاح',
            message: 'تم حذف تعليق الاختبار وتحديث بيانات المراجعات بنجاح'
        );
    }

    public function managementTestStatusHistory(int $test, TestDashboardService $service): JsonResponse
    {
        $result = $service->getManagementTestStatusHistory($test);

        return $this->dataResponse(
            data: new TestManagementStatusHistoryResource($result),
            title: '! تم جلب سجل حالة الاختبار بنجاح'
        );
    }

    public function managementTestReports(TestManagementReportsRequest $request, int $test, TestDashboardService $service): JsonResponse
    {
        $validated = $request->validated();

        $result = $service->getManagementTestReports(
            testId: $test,
            perPage: $validated['per_page'] ?? 20
        );

        return $this->dataResponse(
            data: new TestManagementReportsResource($result),
            title: '! تم جلب سجل إبلاغات الاختبار بنجاح'
        );
    }

    public function requestAiEvaluation(int $test, TestAiEvaluationService $service): JsonResponse
    {
        $result = $service->create(
            user: Auth::user(),
            testId: $test
        );

        return $this->dataResponse(
            data: $result,
            title: '! تم استقبال طلب تقييم الاختبار بالذكاء الاصطناعي بنجاح',
            statusCode: 202
        );
    }

    public function aiEvaluationStatus(int $evaluation, TestAiEvaluationService $service): JsonResponse
    {
        $result = $service->show($evaluation);

        return $this->dataResponse(
            data: $result,
            title: '! تم جلب حالة تقييم الاختبار بالذكاء الاصطناعي بنجاح'
        );
    }

    public function updateManagementTestRevisionRequests(RequestTestRevisionsRequest $request, int $test, TestDashboardService $service): JsonResponse
    {
        $service->updateManagementTestRevisionRequests(
            testId: $test,
            reviewer: $request->user(),
            revisions: $request->validated('revisions')
        );

        return $this->successResponse(
            title: '! تم تعديل طلبات التعديل بنجاح',
            message: 'تم حفظ قائمة التعديلات المطلوبة من المستخدم بنجاح'
        );
    }

}
