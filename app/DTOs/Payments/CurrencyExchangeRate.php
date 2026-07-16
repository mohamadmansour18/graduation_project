<?php

namespace App\DTOs\Payments;

use Illuminate\Support\Carbon;

final readonly class CurrencyExchangeRate
{
    public function __construct(
        public string $sourceCurrency,
        public string $targetCurrency,
        public float $rate,
        public string $provider,
        public Carbon $fetchedAt,
        public ?Carbon $expiresAt = null,
        public bool $isFallback = false,
        public array $metadata = [],
    ) {
    }
}
