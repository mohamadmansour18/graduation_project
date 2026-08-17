<?php

namespace App\Services\AiQuestionGeneration;

use App\Exceptions\Api\AiQuestionGenerationException;
use App\Jobs\ProcessAiQuestionGenerationJob;
use App\Models\User;
use App\Repositories\AiQuestionGeneration\AiQuestionGenerationRepository;
use App\Services\AiQuestionGeneration\Validation\AiQuestionGenerationLocalFileValidationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $startedAt = hrtime(true);
        $phase = 'local_file_validation';

        Log::info('AI question generation request creation started.', [
            'user_id' => $user->id,
            'source_type' => $data['source_type'] ?? null,
            'question_count' => $data['question_count'] ?? null,
            'difficulty_level' => $data['difficulty_level'] ?? null,
            'language' => $data['language'] ?? null,
            'files_count' => count($files),
            'total_files_size_bytes' => array_sum(array_map(
                static fn ($file): int => (int) ($file->getSize() ?: 0),
                $files
            )),
        ]);

        $this->localFileValidationService->validate(
            sourceType: $data['source_type'],
            files: $files
        );

        Log::info('AI question generation local file validation completed.', [
            'user_id' => $user->id,
            'files_count' => count($files),
            'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
        ]);

        $phase = 'request_signature';

        $signature = $this->reuseService->buildSignature(
            user: $user,
            data: $data,
            files: $files
        );

        $phase = 'reusable_request_lookup';

        $reusableRequest = $this->reuseService->findReusableRequest(
            user: $user,
            data: $data,
            signature: $signature
        );

        if ($reusableRequest) {
            Log::info('AI question generation request reused an existing request.', [
                'user_id' => $user->id,
                'generation_request_id' => $reusableRequest->id,
                'request_status' => $reusableRequest->status,
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            ]);

            return [
                'generation_request_id' => $reusableRequest->id,
                'status' => $reusableRequest->status,
                'reused' => true,
            ];
        }

        $phase = 'daily_limit_check';

        $this->assertUserWithinDailyLimit($user);

        $generationRequest = null;

        try {
            $phase = 'request_persistence';

            $generationRequest = DB::transaction(function () use ($user, $data) {
                return $this->repository->createRequest($user, $data);
            });

            Log::info('AI question generation database request created.', [
                'user_id' => $user->id,
                'generation_request_id' => $generationRequest->id,
                'request_status' => $generationRequest->status,
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            ]);

            $phase = 'asset_storage';

            $this->fileStorageService->storeUploadedFiles(
                generationRequest: $generationRequest,
                files: $files,
                fileSignatures: $signature['files']
            );

            Log::info('AI question generation assets stored.', [
                'generation_request_id' => $generationRequest->id,
                'storage_disk' => config('ai_question_generation.storage_disk'),
                'files_count' => count($files),
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            ]);

            $phase = 'reuse_cache_write';

            $this->reuseService->rememberRequest(
                fingerprint: $signature['fingerprint'],
                generationRequestId: $generationRequest->id
            );

            $phase = 'job_dispatch';

            ProcessAiQuestionGenerationJob::dispatch($generationRequest->id)
                ->onQueue(config('ai_question_generation.queue_name'));

            Log::info('AI question generation job dispatched.', [
                'generation_request_id' => $generationRequest->id,
                'queue_connection' => config('queue.default'),
                'queue_name' => config('ai_question_generation.queue_name'),
                'redis_retry_after_seconds' => config('queue.connections.redis.retry_after'),
                'job_timeout_seconds' => 240,
                'job_tries' => 1,
                'horizon_heavy_queues' => config('horizon.defaults.supervisor-heavy.queue', []),
                'horizon_heavy_timeout_seconds' => config('horizon.defaults.supervisor-heavy.timeout'),
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            ]);

            return [
                'generation_request_id' => $generationRequest->id,
                'status' => $generationRequest->status,
                'reused' => false,
            ];

        } catch (\Throwable $exception){
            Log::channel('errors')->error('AI question generation request creation failed before processing.', [
                'user_id' => $user->id,
                'generation_request_id' => $generationRequest?->id,
                'phase' => $phase,
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
                'elapsed_ms' => $this->elapsedMilliseconds($startedAt),
            ]);

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

    ////////////////////////////////////////////////////////////////

    public function getDailyLimitStatus(User $user): array
    {
        $limit = (bool) $user->is_academically_verified
            ? config('ai_question_generation.verified_user_daily_limit')
            : config('ai_question_generation.unverified_user_daily_limit');

        $usedAttempts = $this->repository->countTodayActiveRequestsForUser(
            userId: (int) $user->id
        );

        $remainingAttempts = max(0, $limit - $usedAttempts);

        return [
            'used_attempts' => $usedAttempts,
            'daily_limit' => $limit,
            'remaining_attempts' => $remainingAttempts,
            'attempts_label' => "{$remainingAttempts}/{$limit}",
            'has_reached_daily_limit' => $remainingAttempts === 0,
        ];
    }

    private function elapsedMilliseconds(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 2);
    }
}

