<?php

namespace App\Services\AiQuestionGeneration\Support;

use Illuminate\Support\Facades\Cache;

class AiProviderHealthService
{
    private const string CACHE_PREFIX = 'ai_question_generation:provider_health:';

    public function isAvailable(string $providerName): bool
    {
        return ! Cache::has($this->cacheKey($providerName));
    }

    public function markUnavailable(string $providerName, string $failureCode, string $failureMessage, ?int $cooldownSeconds): void
    {
        if ($cooldownSeconds === null || $cooldownSeconds <= 0) {
            return;
        }

        Cache::put(
            key: $this->cacheKey($providerName),
            value: [
                'provider' => $providerName,
                'failure_code' => $failureCode,
                'failure_message' => $failureMessage,
                'unavailable_until' => now()->addSeconds($cooldownSeconds)->toIso8601String(),
                'cooldown_seconds' => $cooldownSeconds,
            ],
            ttl: $cooldownSeconds
        );
    }

    public function markAvailable(string $providerName): void
    {
        Cache::forget($this->cacheKey($providerName));
    }

    public function unavailableReason(string $providerName): ?array
    {
        $reason = Cache::get($this->cacheKey($providerName));

        return is_array($reason) ? $reason : null;
    }

    private function cacheKey(string $providerName): string
    {
        return self::CACHE_PREFIX . $this->normalizeProviderName($providerName);
    }

    private function normalizeProviderName(string $providerName): string
    {
        return strtolower(trim($providerName));
    }
}
