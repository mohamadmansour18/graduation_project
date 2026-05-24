<?php

namespace App\Services\AiQuestionGeneration;

use App\Models\AiQuestionGenerationRequest;
use App\Models\User;
use App\Repositories\AiQuestionGeneration\AiQuestionGenerationRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class AiQuestionGenerationReuseService
{
    public function __construct(
        private readonly AiQuestionGenerationRepository $repository
    ) {}

    public function buildSignature(User $user, array $data, array $files): array
    {
        $fileSignatures = $this->buildFileSignatures($files);

        $fileHashes = collect($fileSignatures)
            ->pluck('sha256_hash')
            ->sort()
            ->values()
            ->all();

        $fingerprintPayload = [
            'user_id' => $user->id,
            'source_type' => $data['source_type'],
            'question_count' => (int) $data['question_count'],
            'difficulty_level' => $data['difficulty_level'],
            'language' => $data['language'],
            'files' => $fileHashes,
        ];

        return [
            'fingerprint' => hash('sha256', json_encode($fingerprintPayload, JSON_THROW_ON_ERROR)),
            'files' => $fileSignatures,
        ];
    }

    public function findReusableRequest(User $user, array $data, array $signature): ?AiQuestionGenerationRequest
    {
        $fingerprint = $signature['fingerprint'];
        $cacheKey = $this->cacheKey($fingerprint);
        $cachedRequestId = Cache::get($cacheKey);

        if (is_numeric($cachedRequestId)) {
            $cachedRequest = $this->repository->findReusableRequestById(
                id: (int) $cachedRequestId,
                userId: $user->id
            );

            if ($cachedRequest && $this->requestAssetsMatchSignature($cachedRequest, $signature['files'])) {
                return $cachedRequest;
            }

            Cache::forget($cacheKey);
        }

        $matchingRequest = $this->repository->findReusableRequestBySignature(
            userId: $user->id,
            sourceType: $data['source_type'],
            questionCount: (int) $data['question_count'],
            difficultyLevel: $data['difficulty_level'],
            language: $data['language'],
            fileSignatures: $signature['files']
        );

        if ($matchingRequest) {
            $this->rememberRequest($fingerprint, $matchingRequest->id);
        }

        return $matchingRequest;
    }

    public function rememberRequest(string $fingerprint, int $generationRequestId): void
    {
        Cache::put(
            $this->cacheKey($fingerprint),
            $generationRequestId,
            now()->addDays((int) config('ai_question_generation.duplicate_cache_ttl_days', 30))
        );
    }

    private function buildFileSignatures(array $files): array
    {
        return array_map(function (UploadedFile $file, int $index): array {
            return [
                'position' => $index + 1,
                'sha256_hash' => hash_file('sha256', $file->getRealPath()),
                'size_bytes' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ];
        }, array_values($files), array_keys(array_values($files)));
    }

    private function requestAssetsMatchSignature(AiQuestionGenerationRequest $generationRequest, array $fileSignatures): bool
    {
        if ($generationRequest->assets->count() !== count($fileSignatures)) {
            return false;
        }

        $existingHashes = $generationRequest->assets
            ->pluck('sha256_hash')
            ->sort()
            ->values();

        $incomingHashes = collect($fileSignatures)
            ->pluck('sha256_hash')
            ->sort()
            ->values();

        return $existingHashes->all() === $incomingHashes->all();
    }

    private function cacheKey(string $fingerprint): string
    {
        return "ai_question_generation:duplicate:{$fingerprint}";
    }
}
