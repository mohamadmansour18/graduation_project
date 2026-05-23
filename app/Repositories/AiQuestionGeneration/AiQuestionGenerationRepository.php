<?php

namespace App\Repositories\AiQuestionGeneration;

use App\Models\AiQuestionGenerationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AiQuestionGenerationRepository
{
    private const string STATUS_PENDING = 'Pending';
    private const string STATUS_PROCESSING = 'Processing';
    private const string STATUS_COMPLETED = 'Completed';
    private const string STATUS_FAILED = 'Failed';

    public function countTodayActiveRequestsForUser(int $userId): int
    {
        return AiQuestionGenerationRequest::query()
            ->where('user_id', $userId)
            ->whereDate('created_at', today())
            ->whereIn('status', [
                self::STATUS_PENDING,
                self::STATUS_PROCESSING,
                self::STATUS_COMPLETED,
            ])
            ->count();
    }

    public function createRequest(User $user, array $data): AiQuestionGenerationRequest
    {
        return $user->aiQuestionGenerationRequests()->create([
            'source_type' => $data['source_type'],
            'status' => self::STATUS_PENDING,
            'requested_question_count' => $data['question_count'],
            'difficulty_level' => $data['difficulty_level'],
            'language' => $data['language'],
        ]);
    }

    public function createAsset(AiQuestionGenerationRequest $generationRequest, array $data): void
    {

        $generationRequest->assets()->create([
            'storage_disk' => $data['storage_disk'],
            'storage_path' => $data['storage_path'],
            'original_name' => $data['original_name'],
            'mime_type' => $data['mime_type'],
            'size_bytes' => $data['size_bytes'],
            'sha256_hash' => $data['sha256_hash'],
            'position' => $data['position'],
        ]);
    }

    public function findForUserWithAssets(int $id, int $userId): Model|Builder|null
    {
        return AiQuestionGenerationRequest::query()
            ->with('assets')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    public function findWithAssetsById(int $id): Builder|AiQuestionGenerationRequest|null
    {
        return AiQuestionGenerationRequest::query()
            ->with('assets')
            ->where('id', $id)
            ->first();
    }

    public function markAsProcessing(AiQuestionGenerationRequest $generationRequest): void
    {
        $generationRequest->update([
            'status' => self::STATUS_PROCESSING,
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(
        AiQuestionGenerationRequest $generationRequest,
        array $questions,
        string $provider,
        string $model
    ): void {
        $generationRequest->update([
            'status' => self::STATUS_COMPLETED,
            'provider' => $provider,
            'model' => $model,
            'generated_questions_json' => $questions,
            'completed_at' => now(),
            'failure_code' => null,
            'failure_message' => null,
        ]);
    }

    public function markAsFailed(
        AiQuestionGenerationRequest $generationRequest,
        string $failureCode,
        string $failureMessage
    ): void {
        $generationRequest->update([
            'status' => self::STATUS_FAILED,
            'failure_code' => $failureCode,
            'failure_message' => $failureMessage,
            'failed_at' => now(),
        ]);
    }

    public function markAssetAsDeletedFromStorage(int $assetId): void
    {
        \App\Models\AiQuestionGenerationAsset::query()
            ->where('id', $assetId)
            ->update([
                'deleted_from_storage_at' => now(),
            ]);
    }
}
