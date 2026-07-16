<?php

namespace App\Contracts\Payments;

use App\DTOs\Payments\CurrencyExchangeRate;

interface ExchangeRateProviderInterface
{
    public function getRate(string $sourceCurrency, string $targetCurrency): CurrencyExchangeRate;
}
