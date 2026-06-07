<?php

namespace App\Http\Controllers\V1\Tests;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tests\ListTestReviewsRequest;
use App\Http\Requests\Tests\StoreTestAttemptRequest;
use App\Http\Requests\Tests\UpdateTestRequest;
use App\Http\Resources\Tests\MyPrivateTestDetailsResource;
use App\Http\Resources\Tests\MyPublicTestDetailsResource;
use App\Http\Resources\Tests\MyReviewResource;
use App\Http\Resources\Tests\TestContentResource;
use App\Http\Resources\Tests\TestDetailsResource;
use App\Http\Resources\Tests\TestReviewResource;
use App\Http\Resources\Tests\TestStatusHistoryResource;
use App\Http\Resources\Tests\UpdateTestResultResource;
use App\Services\Tests\TestAttemptService;
use App\Services\Tests\TestService;
use App\Services\Tests\UpdateTestService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TestController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TestService $testService,
        private readonly UpdateTestService $updateTestService,
    ) {}

    public function show(int $testId): JsonResponse
    {
        $details = $this->testService->getDetailsForUser(
            testId: $testId,
            viewerId: Auth::id()
        );

        return $this->dataResponse(
            data: new TestDetailsResource($details),
            title: '! تم جلب تفاصيل الاختبار بنجاح'
        );
    }

    public function showMyPrivateTestDetails(int $testId): JsonResponse
    {
        $details = $this->testService->getMyPrivateTestDetails(
            testId: $testId,
            ownerId: auth()->id()
        );

        return $this->dataResponse(
            data: new MyPrivateTestDetailsResource($details),
            title: '! تم جلب تفاصيل الاختبار بنجاح'
        );
    }

    public function showMyPublicTestDetails(int $testId): JsonResponse
    {
        $details = $this->testService->getMyPublicTestDetails(
            testId: $testId,
            ownerId: auth()->id()
        );

        return $this->dataResponse(
            data: new MyPublicTestDetailsResource($details),
            title: '! تم جلب تفاصيل الاختبار بنجاح'
        );
    }

    public function previewSampleQuestions(int $testId): JsonResponse
    {
        $questions = $this->testService->getPreviewQuestionsForViewer($testId , Auth::id());

        return $this->dataResponse(
            data: $questions,
            title: '! تم جلب عينة الاختبار بنجاح'
        );
    }

    public function reviews(ListTestReviewsRequest $request, int $testId): JsonResponse
    {
        $result = $this->testService->listRatingForTest(
            testId: $testId,
            viewerId: Auth::id(),
            rating: $request->ratingFilter(),
            context: 'other',
            excludeViewerReview: true,
            mustBeApproved: true
        );

        $paginator = $result['reviews'];

        return $this->dataResponse(
            data : [
                'summary' => $result['summary'],
                'my_review' => new MyReviewResource($result['my_review']),
                'reviews' => TestReviewResource::collection($paginator->items()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more_pages' => $paginator->hasMorePages(),
                ],
            ],
            title: '! تم جلب تقييمات الاختبار بنجاح'
        );
    }

    public function shareLink(int $testId): JsonResponse
    {
        $data = $this->testService->getShareLink($testId);

        return $this->dataResponse(
            data: $data,
            title: '! تم جلب رابط المشاركة بنجاح'
        );
    }

    public function showByShareSlug(string $slug): JsonResponse
    {
        $userId = Auth::id();

        $data = $this->testService->getTestDetailsBySlug($slug , $userId);

        return $this->dataResponse($data);
    }
    public function content(int $testId): JsonResponse
    {
        $data = $this->testService->getContent(
            testId: $testId,
            viewerId: Auth::id()
        );

        return $this->dataResponse(
            data: new TestContentResource($data),
            title: '! تم جلب محتوى الاختبار بنجاح'
        );
    }

    //////////////////////////////////////////////////////////////
    public function showMyTestReviews(ListTestReviewsRequest $request, int $testId): JsonResponse
    {
        $result = $this->testService->listRatingForTest(
            testId: $testId,
            viewerId: Auth::id(),
            rating: $request->ratingFilter(),
            context: 'my',
            excludeViewerReview: true,
            mustBeApproved: false
        );

        $paginator = $result['reviews'];

        return $this->dataResponse(
            data : [
                'summary' => $result['summary'],
                'reviews' => TestReviewResource::collection($paginator->items()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'has_more_pages' => $paginator->hasMorePages(),
                ],
            ],
            title: '! تم جلب تقييمات الاختبار بنجاح'
        );
    }

    //////////////////////////////////////////////////////////////

    public function statusHistory(int $testId): JsonResponse
    {
        $histories = $this->testService->getTestStatusHistoryForOwner(
            testId: $testId,
            ownerId: Auth::id()
        );

        return $this->dataResponse(
            data: TestStatusHistoryResource::collection($histories),
            title: '! تم جلب سجل حالة الاختبار بنجاح'
        );
    }

    //////////////////////////////////////////////////////////////

    public function destroy(int $testId): JsonResponse
    {
        $this->testService->deleteTest(
            testId: $testId,
            userId: Auth::id()
        );

        return $this->successResponse(
            title: '! تم حذف الاختبار بنجاح',
            message: 'تم حذف الاختبار المحدد بنجاح ولن تسطيع الوصول اليه بعدد الان'
        );
    }

    //////////////////////////////////////////////////////////////

    public function update(int $testId, UpdateTestRequest $request): JsonResponse
    {
        $result = $this->updateTestService->updateTest(
            testId: $testId,
            userId: (int) auth()->id(),
            payload: $request->validated()
        );

        return $this->dataResponse(
            data: new UpdateTestResultResource($result),
            title: '! تم تعديل الاختبار بنجاح'
        );
    }

    //////////////////////////////////////////////////////////////

    public function storeAttempt(int $testId, StoreTestAttemptRequest $request, TestAttemptService $testAttemptService): JsonResponse
    {
        $testAttemptService->registerAttempt(
            testId: $testId,
            userId: (int) auth()->id(),
            mode: $request->validated('mode')
        );

        return $this->successResponse(
            title: '! تم تسجيل التفاعل بنجاح',
            message: 'تم تسجيل تفاعلك مع الاختبار بنجاح'
        );
    }


}
