<?php

namespace App\Http\Controllers\V1\Tests;

use App\Http\Requests\Tests\StoreTestReviewReportRequest;
use App\Services\Tests\TestReportReviewService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TestReportReviewController
{
    use ApiResponse;

    public function __construct(
        private readonly TestReportReviewService $testReviewReportService
    ) {}

    public function store(StoreTestReviewReportRequest $request, int $reviewId): JsonResponse
    {
        $data = $this->testReviewReportService->store(
            reviewId: $reviewId,
            reporterUserId: Auth::id(),
            reason: $request->validated('reason'),
            description: $request->validated('description') ?? null
        );

        return $this->dataResponse(
            data: $data,
            title: '! تم إرسال البلاغ بنجاح'
        );
    }
}
