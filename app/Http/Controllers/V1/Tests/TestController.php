<?php

namespace App\Http\Controllers\V1\Tests;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tests\ListTestReviewsRequest;
use App\Http\Resources\MyReviewResource;
use App\Http\Resources\Tests\MyPrivateTestDetailsResource;
use App\Http\Resources\Tests\MyPublicTestDetailsResource;
use App\Http\Resources\Tests\TestDetailsResource;
use App\Http\Resources\Tests\TestReviewResource;
use App\Services\Tests\TestService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TestController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TestService $testService
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
            excludeViewerReview: true
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
}
