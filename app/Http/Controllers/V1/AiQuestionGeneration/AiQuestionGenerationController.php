<?php

namespace App\Http\Controllers\V1\AiQuestionGeneration;

use App\Exceptions\Api\AiQuestionGenerationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AI\StoreAiQuestionGenerationRequest;
use App\Services\AiQuestionGeneration\AiQuestionGenerationService;
use App\Trait\ApiResponse;
use Illuminate\Http\JsonResponse;

class AiQuestionGenerationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AiQuestionGenerationService $service
    ) {}

    /**
     * @throws AiQuestionGenerationException
     */
    public function store(StoreAiQuestionGenerationRequest $request): JsonResponse
    {
        $data = $this->service->create(
            user: $request->user(),
            data: $request->validated(),
            files: $request->file('files', [])
        );

        return $this->dataResponse(
            data: $data,
            title: 'تم استقبال طلب توليد الأسئلة بنجاح',
            statusCode: 202
        );
    }

    public function show(int $id): JsonResponse
    {
        $data = $this->service->show(
            user: request()->user(),
            generationRequestId: $id
        );

        return $this->dataResponse(
            data: $data,
            title: 'تم جلب حالة طلب توليد الأسئلة بنجاح'
        );
    }

    public function aiGenerationDailyLimit(): JsonResponse
    {
        $result = $this->service->getDailyLimitStatus(auth()->user());

        return $this->dataResponse(
            data: $result,
            title: '! تم جلب عدد المحاولات اليومية بنجاح'
        );
    }
}
