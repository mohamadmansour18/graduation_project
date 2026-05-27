<?php

namespace App\Services\AiQuestionGeneration;

use App\Exceptions\Api\AiQuestionGenerationException;
use App\Jobs\ProcessAiQuestionGenerationJob;
use App\Models\User;
use App\Repositories\AiQuestionGeneration\AiQuestionGenerationRepository;
use App\Services\AiQuestionGeneration\Validation\AiQuestionGenerationLocalFileValidationService;
use Illuminate\Support\Facades\DB;
use JsonException;

class AiQuestionGenerationService
{
    public function __construct(
        private readonly AiQuestionGenerationRepository $repository,
        private readonly AiQuestionGenerationFileStorageService $fileStorageService,
        private readonly AiQuestionGenerationReuseService $reuseService,
        private readonly AiQuestionGenerationLocalFileValidationService $localFileValidationService
    ) {}

    /**
     * @throws AiQuestionGenerationException
     * @throws JsonException
     */
    public function create(User $user, array $data, array $files): array
    {
        $this->localFileValidationService->validate(
            sourceType: $data['source_type'],
            files: $files
        );

        $signature = $this->reuseService->buildSignature(
            user: $user,
            data: $data,
            files: $files
        );

        $reusableRequest = $this->reuseService->findReusableRequest(
            user: $user,
            data: $data,
            signature: $signature
        );

        if ($reusableRequest) {
            return [
                'generation_request_id' => $reusableRequest->id,
                'status' => $reusableRequest->status,
                'reused' => true,
            ];
        }

        $this->assertUserWithinDailyLimit($user);

        $generationRequest = null;

        try {

            $generationRequest = DB::transaction(function () use ($user, $data) {
                return $this->repository->createRequest($user, $data);
            });

            $this->fileStorageService->storeUploadedFiles(
                generationRequest: $generationRequest,
                files: $files,
                fileSignatures: $signature['files']
            );

            $this->reuseService->rememberRequest(
                fingerprint: $signature['fingerprint'],
                generationRequestId: $generationRequest->id
            );

            ProcessAiQuestionGenerationJob::dispatch($generationRequest->id)
                ->onQueue(config('ai_question_generation.queue_name'));


            return [
                'generation_request_id' => $generationRequest->id,
                'status' => $generationRequest->status,
                'reused' => false,
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
            'question_actually_generated' => $generationRequest->status === 'Completed'
                ? count($generationRequest->generated_questions_json ?? [])
                : 0,
            'difficulty_level' => $generationRequest->difficulty_level,
            'language' => $generationRequest->language,
            'provider' => $generationRequest->provider ?? null,
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

