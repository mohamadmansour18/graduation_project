<?php

namespace App\Http\Controllers\V1\Tests;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tests\StoreTestReportRequest;
use App\Services\Tests\TestReportService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TestReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TestReportService $testReportService
    ) {}

    public function store(StoreTestReportRequest $request, int $testId): JsonResponse
    {
        $data  = $this->testReportService->store(
            testId: $testId,
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
