<?php

namespace App\DTOs\Payments;

final readonly class CreateCheckoutSessionData
{
    public function __construct(
        public int $purchaseId,
        public int $attemptId,
        public int $testId,
        public int $buyerUserId,
        public int $sellerUserId,
        public string $testTitle,
        public PurchaseMoneyBreakdown $money,
        public string $successUrl,
        public string $cancelUrl,
        public int $expiresAt,
        public array $metadata = [],
    ) {
    }
}
