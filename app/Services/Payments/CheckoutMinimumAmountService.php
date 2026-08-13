<?php

namespace App\Services\Payments;

use App\DTOs\Payments\CurrencyConversionResult;

class CheckoutMinimumAmountService
{
    public function __construct(
        private readonly CurrencyConversionService $currencyConversionService,
    ) {
    }

    public function assess(float $sourceAmount, string $sourceCurrency, string $checkoutCurrency): array
    {
        $conversion = $this->currencyConversionService->convert(
            amount: $sourceAmount,
            sourceCurrency: $sourceCurrency,
            targetCurrency: $checkoutCurrency,
        );

        $requiredCheckoutAmount = $this->requiredCheckoutAmount();
        $minimumSourceAmount = $this->minimumSourceAmount($conversion, $requiredCheckoutAmount);

        return [
            'conversion' => $conversion,
            'is_sufficient' => $conversion->convertedAmount >= $requiredCheckoutAmount,
            'required_checkout_amount' => $requiredCheckoutAmount,
            'minimum_source_amount' => $minimumSourceAmount,
        ];
    }

    private function requiredCheckoutAmount(): float
    {
        $stripeMinimum = max(0.01, (float) config('payments.minimum_checkout_amount', 0.50));
        $safetyMarginPercent = max(0, (float) config('payments.minimum_checkout_safety_margin_percent', 20));

        return ceil($stripeMinimum * (1 + ($safetyMarginPercent / 100)) * 100) / 100;
    }

    private function minimumSourceAmount(CurrencyConversionResult $conversion, float $requiredCheckoutAmount): float
    {
        if ($conversion->exchangeRate <= 0) {
            return 0;
        }

        return ceil(($requiredCheckoutAmount / $conversion->exchangeRate) * 100) / 100;
    }
}
