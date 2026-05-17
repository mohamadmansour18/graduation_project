<?php

namespace App\DTOs\Payments;

final readonly class PurchaseMoneyBreakdown
{
    public function __construct(
        public float $grossAmount,
        public float $platformFeeAmount,
        public float $sellerNetAmount,
        public string $currency,
    )
    {}

    public function grossAmountInMinorUnit(): int
    {
        return (int) round($this->grossAmount * 100);
    }
}
