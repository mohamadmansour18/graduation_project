<?php

namespace App\Services\AiQuestionGeneration\Support;

class AiProviderFailureDecision
{
    public function __construct(
        public readonly bool $shouldTryNextProvider,
        public readonly string $failureCode,
        public readonly string $failureMessage,
        public readonly ?int $cooldownSeconds = null,
    ) {}

    //this function said we can use another provider
    public static function retryable(string $failureCode, string $failureMessage, ?int $cooldownSeconds = null): self
    {
        return new self(
            shouldTryNextProvider: true,
            failureCode: $failureCode,
            failureMessage: $failureMessage,
            cooldownSeconds: $cooldownSeconds,
        );
    }

    //this function said we can not try to use another provider
    public static function final(string $failureCode, string $failureMessage): self
    {
        return new self(
            shouldTryNextProvider: false,
            failureCode: $failureCode,
            failureMessage: $failureMessage,
            cooldownSeconds: null,
        );
    }
}
