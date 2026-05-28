<?php

namespace App\Services\AiQuestionGeneration\Support;

use App\Exceptions\Api\ApiException;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class AiProviderFailureClassifier
{
    public function classify(Throwable $exception): AiProviderFailureDecision
    {
        if ($exception instanceof ConnectionException) {
            return AiProviderFailureDecision::retryable(
                failureCode: 'AI_PROVIDER_CONNECTION_FAILED',
                failureMessage: 'تعذر الاتصال بمزود الذكاء الاصطناعي، سيتم تجربة مزود آخر إن وجد.',
                cooldownSeconds: $this->cooldownSeconds('connection_failed_seconds')
            );
        }

        if ($exception instanceof ApiException) {
            return $this->classifyApiException($exception);
        }

        return AiProviderFailureDecision::final(
            failureCode: 'AI_GENERATION_FAILED',
            failureMessage: 'فشل توليد الأسئلة، يرجى المحاولة لاحقاً.'
        );
    }

    private function classifyApiException(ApiException $exception): AiProviderFailureDecision
    {
        $failureCode = (string) (
            $exception->getContext()['failure_code']
            ?? 'AI_GENERATION_FAILED'
        );

        $failureMessage = $exception->getMessages();

        if ($this->isRetryableFailureCode($failureCode)) {
            return AiProviderFailureDecision::retryable(
                failureCode: $failureCode,
                failureMessage: $failureMessage,
                cooldownSeconds: $this->cooldownForFailureCode($failureCode)
            );
        }

        return AiProviderFailureDecision::final(
            failureCode: $failureCode,
            failureMessage: $failureMessage
        );
    }

    private function isRetryableFailureCode(string $failureCode): bool
    {
        return in_array($failureCode, [
            'AI_PROVIDER_RATE_LIMITED',
            'AI_PROVIDER_CONNECTION_FAILED',
            'AI_PROVIDER_TEMPORARILY_UNAVAILABLE',
            'AI_PROVIDER_TIMEOUT',
            'AI_PROVIDER_INVALID_RESPONSE',
            'AI_PROVIDER_UNSUPPORTED_SOURCE_TYPE',
            'AI_PROVIDER_AUTH_FAILED',
            'AI_PROVIDER_REQUEST_REJECTED',
            'AI_PROVIDER_REQUEST_FAILED',
            'AI_PROVIDER_UPLOAD_URL_MISSING',
            'AI_PROVIDER_UPLOADED_FILE_RESPONSE_INVALID',
            'PROVIDER_API_KEY_MISSING',
            'PROVIDER_NOT_ENOUGH_EDUCATIONAL_CONTENT',
            'CONTENT_NOT_EDUCATIONAL',
            'INVALID_GENERATED_QUESTIONS',
            'AI_ASSET_TEXT_EXTRACTION_FAILED',
        ], true);
    }

    private function cooldownForFailureCode(string $failureCode): ?int
    {
        return match ($failureCode) {
            'AI_PROVIDER_RATE_LIMITED'
            => $this->cooldownSeconds('rate_limited_seconds'),

            'AI_PROVIDER_CONNECTION_FAILED'
            => $this->cooldownSeconds('connection_failed_seconds'),

            'AI_PROVIDER_TEMPORARILY_UNAVAILABLE',
            'AI_PROVIDER_TIMEOUT',
            'AI_PROVIDER_INVALID_RESPONSE'
            => $this->cooldownSeconds('temporary_unavailable_seconds'),

            'AI_PROVIDER_UNSUPPORTED_SOURCE_TYPE'
            => null,

            'AI_PROVIDER_AUTH_FAILED',
            'AI_PROVIDER_REQUEST_REJECTED',
            'AI_PROVIDER_REQUEST_FAILED',
            'AI_PROVIDER_UPLOAD_URL_MISSING',
            'AI_PROVIDER_UPLOADED_FILE_RESPONSE_INVALID',
            'PROVIDER_API_KEY_MISSING',
            'PROVIDER_NOT_ENOUGH_EDUCATIONAL_CONTENT',
            'CONTENT_NOT_EDUCATIONAL',
            'INVALID_GENERATED_QUESTIONS',
            'AI_ASSET_TEXT_EXTRACTION_FAILED'
            => null,

            default => null,
        };
    }

    private function cooldownSeconds(string $key): ?int
    {
        $seconds = config("ai_question_generation.provider_routing.cooldowns.{$key}");

        if (! is_numeric($seconds)) {
            return null;
        }

        return max(0, (int) $seconds);
    }

}
