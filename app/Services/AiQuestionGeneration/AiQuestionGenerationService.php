<?php

namespace App\Services\AiQuestionGeneration;

use App\Exceptions\Api\AiQuestionGenerationException;
use App\Jobs\ProcessAiQuestionGenerationJob;
use App\Models\User;
use App\Repositories\AiQuestionGeneration\AiQuestionGenerationRepository;
use Illuminate\Support\Facades\DB;

class AiQuestionGenerationService
{
    public function __construct(
        private readonly AiQuestionGenerationRepository $repository,
        private readonly AiQuestionGenerationFileStorageService $fileStorageService
    ) {}

    /**
     * @throws AiQuestionGenerationException
     */
    public function create(User $user, array $data, array $files): array
    {
        $this->assertUserWithinDailyLimit($user);

        $generationRequest = null;

        try {

            $generationRequest = DB::transaction(function () use ($user, $data) {
                return $this->repository->createRequest($user, $data);
            });

            $this->fileStorageService->storeUploadedFiles(
                generationRequest: $generationRequest,
                files: $files
            );

            ProcessAiQuestionGenerationJob::dispatch($generationRequest->id)
                ->onQueue(config('ai_question_generation.queue_name'));


            return [
                'generation_request_id' => $generationRequest->id,
                'status' => $generationRequest->status,
            ];

        } catch (\Throwable $exception){

            if ($generationRequest !== null) {
                $this->fileStorageService->deleteRequestDirectory($generationRequest->id);
            }

            throw AiQuestionGenerationException::failedToStoreFiles();
        }
    }

    private function assertUserWithinDailyLimit(User $user): void
    {
        $limit = (bool) $user->is_academically_verified
            ? config('ai_question_generation.verified_user_daily_limit')
            : config('ai_question_generation.unverified_user_daily_limit');

        $todayRequestsCount = $this->repository->countTodayActiveRequestsForUser($user->id);

        if ($todayRequestsCount >= $limit) {
            throw AiQuestionGenerationException::dailyLimitExceeded($limit);
        }
    }

    public function show(User $user, int $generationRequestId): array
    {
        $generationRequest = $this->repository->findForUserWithAssets(
            id: $generationRequestId,
            userId: $user->id
        );

        if (! $generationRequest) {
            throw AiQuestionGenerationException::generationRequestNotFound();
        }

        return [
            'id' => $generationRequest->id,
            'status' => $generationRequest->status,
            'source_type' => $generationRequest->source_type,
            'requested_question_count' => $generationRequest->requested_question_count,
            'difficulty_level' => $generationRequest->difficulty_level,
            'language' => $generationRequest->language,
            'questions' => $generationRequest->status === 'Completed'
                ? $this->formatQuestionsForResponse($generationRequest->generated_questions_json ?? [])
                : null,
            'failure' => $generationRequest->status === 'Failed'
                ? [
                    'code' => $generationRequest->failure_code,
                    'message' => $generationRequest->failure_message,
                ]
                : null,
        ];
    }

    private function formatQuestionsForResponse(array $questions): array
    {
        return array_map(function (array $question): array {
            return [
                'question_text' => $question['question_text'] ?? '',
                'hint_text' => $question['hint_text'] ?? null,
                'options' => $question['options'] ?? [],
            ];
        }, $questions);
    }

}

