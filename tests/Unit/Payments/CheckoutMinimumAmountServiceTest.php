<?php

namespace Tests\Unit\Payments;

use App\DTOs\Payments\CurrencyConversionResult;
use App\Services\Payments\CheckoutMinimumAmountService;
use App\Services\Payments\CurrencyConversionService;
use Mockery;
use Tests\TestCase;

class CheckoutMinimumAmountServiceTest extends TestCase
{
    public function test_it_requires_the_stripe_minimum_plus_the_configured_safety_margin(): void
    {
        config([
            'payments.minimum_checkout_amount' => 0.50,
            'payments.minimum_checkout_safety_margin_percent' => 20,
        ]);

        $conversionService = Mockery::mock(CurrencyConversionService::class);
        $conversionService->shouldReceive('convert')
            ->once()
            ->with(4825.0, 'syp', 'usd')
            ->andReturn($this->conversion(amount: 4825, convertedAmount: 0.40));

        $assessment = (new CheckoutMinimumAmountService($conversionService))
            ->assess(4825, 'syp', 'usd');

        $this->assertFalse($assessment['is_sufficient']);
        $this->assertSame(0.60, $assessment['required_checkout_amount']);
        $this->assertSame(7200.0, $assessment['minimum_source_amount']);
    }

    public function test_it_accepts_an_amount_that_meets_the_protected_minimum(): void
    {
        config([
            'payments.minimum_checkout_amount' => 0.50,
            'payments.minimum_checkout_safety_margin_percent' => 20,
        ]);

        $conversionService = Mockery::mock(CurrencyConversionService::class);
        $conversionService->shouldReceive('convert')
            ->once()
            ->with(7500.0, 'syp', 'usd')
            ->andReturn($this->conversion(amount: 7500, convertedAmount: 0.63));

        $assessment = (new CheckoutMinimumAmountService($conversionService))
            ->assess(7500, 'syp', 'usd');

        $this->assertTrue($assessment['is_sufficient']);
        $this->assertSame(7200.0, $assessment['minimum_source_amount']);
    }

    private function conversion(float $amount, float $convertedAmount): CurrencyConversionResult
    {
        return new CurrencyConversionResult(
            sourceAmount: $amount,
            sourceCurrency: 'syp',
            convertedAmount: $convertedAmount,
            targetCurrency: 'usd',
            exchangeRate: 1 / 12000,
            provider: 'manual_fallback',
            fetchedAt: now(),
            expiresAt: now()->addMinutes(15),
            isFallback: true,
        );
    }
}
