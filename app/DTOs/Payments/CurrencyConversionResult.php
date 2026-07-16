<?php

namespace App\DTOs\Payments;

use Illuminate\Support\Carbon;

final readonly class CurrencyConversionResult
{
    public function __construct(
        public float $sourceAmount,
        public string $sourceCurrency,
        public float $convertedAmount,
        public string $targetCurrency,
        public float $exchangeRate,
        public string $provider,
        public ?Carbon $fetchedAt = null,
        public ?Carbon $expiresAt = null,
        public bool $isFallback = false,
    ) {
    }

    public function convertedAmountInMinorUnit(): int
    {
        return (int) round($this->convertedAmount * 100);
    }
}
