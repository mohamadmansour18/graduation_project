<?php

namespace App\Services\Payments;

use App\DTOs\Payments\PurchaseMoneyBreakdown;
use App\Exceptions\Api\PaymentException;

class PurchaseMoneyCalculator
{
    public function calculate(float $grossAmount, string $currency): PurchaseMoneyBreakdown
    {
        if ($grossAmount <= 0) {
            throw PaymentException::invalidTestPrice();
        }

        $platformFeePercent = (float) config('payments.platform_fee_percent', 20);

        $platformFeeAmount = round(
            ($grossAmount * $platformFeePercent) / 100,
            2
        );

        $sellerNetAmount = round(
            $grossAmount - $platformFeeAmount,
            2
        );

        return new PurchaseMoneyBreakdown(
            grossAmount: round($grossAmount, 2),
            platformFeeAmount: $platformFeeAmount,
            sellerNetAmount: $sellerNetAmount,
            currency: $currency,
        );
    }
}
