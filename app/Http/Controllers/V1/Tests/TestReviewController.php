<?php

namespace App\Http\Controllers\V1\Tests;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tests\StoreTestReviewRequest;
use App\Http\Requests\Tests\UpdateTestReviewRequest;
use App\Services\Tests\TestReviewService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TestReviewController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TestReviewService $testReviewInteractionService
    ) {}

    public function store(StoreTestReviewRequest $request, int $testId): JsonResponse
    {
        $this->testReviewInteractionService->store(
            testId: $testId,
            userId: Auth::id(),
            rating: (int) $request->validated('rating'),
            reviewText: $request->validated('review_text')
        );

        return $this->successResponse(
            message: 'تم إضافة التقييم بنجاح'
        );
    }

    public function update(UpdateTestReviewRequest $request, int $testId): JsonResponse
    {
        $validated = $request->validated();

        $this->testReviewInteractionService->update(
            testId: $testId,
            userId: Auth::id(),
            rating: array_key_exists('rating', $validated) ? (int) $validated['rating'] : null,
            reviewText: array_key_exists('review_text', $validated) ? $validated['review_text'] : null
        );

        return $this->successResponse(
            message: 'تم تعديل التقييم بنجاح'
        );
    }

    public function destroy(int $testId): JsonResponse
    {
        $this->testReviewInteractionService->delete(
            testId: $testId,
            userId: Auth::id()
        );

        return $this->successResponse(
            message: 'تم حذف التقييم بنجاح'
        );
    }
}
