<?php

namespace App\Http\Controllers\V1\Tests;

use App\Http\Controllers\Controller;
use App\Http\Resources\Tests\TestRevisionRequestResource;
use App\Services\Tests\TestRevisionRequestService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class TestRevisionRequestController extends Controller
{
    use ApiResponse;
    public function __construct(
        private readonly TestRevisionRequestService $testRevisionRequestService
    )
    {}
    public function revisionRequestsByRound(int $testId, int $roundId): JsonResponse
    {
        $result = $this->testRevisionRequestService->getByRoundForOwner(
            testId: $testId,
            roundId: $roundId,
            ownerId: auth()->id()
        );

        return $this->dataResponse(
            data : TestRevisionRequestResource::collection($result),
            title: '! تم جلب التعديلات المطلوبة بنجاح',
        );
    }
}
