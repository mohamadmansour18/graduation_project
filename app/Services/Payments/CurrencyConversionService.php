<?php

namespace App\Services\Payments;

use App\Contracts\Payments\ExchangeRateProviderInterface;
use App\DTOs\Payments\CurrencyConversionResult;
use App\DTOs\Payments\CurrencyExchangeRate;
use App\Exceptions\Api\PaymentException;
use App\Repositories\Payments\CurrencyExchangeRateRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class CurrencyConversionService
{
    public function __construct(
        private readonly ExchangeRateProviderInterface $exchangeRateProvider,
        private readonly CurrencyExchangeRateRepository $exchangeRateRepository,
    ) {
    }

    public function convert(float $amount, string $sourceCurrency, string $targetCurrency): CurrencyConversionResult
    {
        $sourceCurrency = strtolower($sourceCurrency);
        $targetCurrency = strtolower($targetCurrency);

        if ($amount <= 0) {
            throw PaymentException::invalidTestPrice();
        }

        if ($sourceCurrency === $targetCurrency) {
            return new CurrencyConversionResult(
                sourceAmount: round($amount, 2),
                sourceCurrency: $sourceCurrency,
                convertedAmount: round($amount, 2),
                targetCurrency: $targetCurrency,
                exchangeRate: 1,
                provider: 'same_currency',
                fetchedAt: now(),
                expiresAt: now()->addDay(),
            );
        }

        $rate = $this->rateFor($sourceCurrency, $targetCurrency);
        $convertedAmount = round($amount * $rate->rate, 2);

        if ($convertedAmount <= 0) {
            throw PaymentException::currencyConversionUnavailable();
        }

        return new CurrencyConversionResult(
            sourceAmount: round($amount, 2),
            sourceCurrency: $sourceCurrency,
            convertedAmount: $convertedAmount,
            targetCurrency: $targetCurrency,
            exchangeRate: $rate->rate,
            provider: $rate->provider,
            fetchedAt: $rate->fetchedAt,
            expiresAt: $rate->expiresAt,
            isFallback: $rate->isFallback,
        );
    }

    private function rateFor(string $sourceCurrency, string $targetCurrency): CurrencyExchangeRate
    {
        $cacheKey = $this->cacheKey($sourceCurrency, $targetCurrency);

        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return new CurrencyExchangeRate(
                sourceCurrency: $cached['source_currency'],
                targetCurrency: $cached['target_currency'],
                rate: (float) $cached['rate'],
                provider: $cached['provider'],
                fetchedAt: Carbon::parse($cached['fetched_at']),
                expiresAt: isset($cached['expires_at']) && $cached['expires_at']
                    ? Carbon::parse($cached['expires_at'])
                    : null,
                isFallback: (bool) ($cached['is_fallback'] ?? false),
            );
        }

        $marketOpenedAt = $this->currentMarketOpenedAt();
        $nextMarketOpenAt = $this->nextMarketOpenAt();

        $storedActiveRate = $this->exchangeRateRepository->latestActiveRateForMarketWindow(
            sourceCurrency: $sourceCurrency,
            targetCurrency: $targetCurrency,
            marketOpenedAt: $marketOpenedAt,
        );

        if ($storedActiveRate) {
            $rate = $this->exchangeRateRepository->toDto($storedActiveRate, false);
            $this->cacheRateUntil($cacheKey, $rate, $nextMarketOpenAt);

            return $rate;
        }

        try {
            $rate = $this->rateForCurrentMarketWindow(
                $this->exchangeRateProvider->getRate($sourceCurrency, $targetCurrency),
            );

            $this->exchangeRateRepository->store($rate);
            $this->cacheRateUntil($cacheKey, $rate, $nextMarketOpenAt);

            return $rate;
        } catch (Throwable $exception) {
            Log::channel('errors')->warning('Currency exchange provider failed', [
                'source_currency' => $sourceCurrency,
                'target_currency' => $targetCurrency,
                'message' => $exception->getMessage(),
            ]);

            $fallbackRate = $this->fallbackRate($sourceCurrency, $targetCurrency);

            if (! $fallbackRate) {
                throw PaymentException::currencyConversionUnavailable();
            }

            $this->cacheRate(
                cacheKey: $cacheKey,
                rate: $fallbackRate,
                minutes: (int) config('payments.currency_conversion.cache_fallback_minutes', 15),
            );

            return $fallbackRate;
        }
    }

    private function fallbackRate(string $sourceCurrency, string $targetCurrency): ?CurrencyExchangeRate
    {
        $storedRate = $this->exchangeRateRepository->latestUsableRate(
            sourceCurrency: $sourceCurrency,
            targetCurrency: $targetCurrency,
            maxStaleDays: (int) config('payments.currency_conversion.fallback_max_stale_days', 3),
        );

        if ($storedRate) {
            return $this->exchangeRateRepository->toDto($storedRate, true);
        }

        $manualRate = $this->manualFallbackRate($sourceCurrency, $targetCurrency);

        if (! $manualRate) {
            return null;
        }

        return new CurrencyExchangeRate(
            sourceCurrency: $sourceCurrency,
            targetCurrency: $targetCurrency,
            rate: $manualRate,
            provider: 'manual_fallback',
            fetchedAt: now(),
            expiresAt: now()->addMinutes((int) config('payments.currency_conversion.cache_fallback_minutes', 15)),
            isFallback: true,
        );
    }

    private function manualFallbackRate(string $sourceCurrency, string $targetCurrency): ?float
    {
        $sypPerUsd = config('payments.currency_conversion.manual_fallback.syp_per_usd');

        if ($sourceCurrency === 'syp' && $targetCurrency === 'usd' && is_numeric($sypPerUsd) && (float) $sypPerUsd > 0) {
            return 1 / (float) $sypPerUsd;
        }

        $directRate = config("payments.currency_conversion.manual_fallback.rates.{$sourceCurrency}.{$targetCurrency}");

        if (is_numeric($directRate) && (float) $directRate > 0) {
            return (float) $directRate;
        }

        return null;
    }

    private function rateForCurrentMarketWindow(CurrencyExchangeRate $rate): CurrencyExchangeRate
    {
        $marketOpenedAt = $this->currentMarketOpenedAt();
        $nextMarketOpenAt = $this->nextMarketOpenAt();

        return new CurrencyExchangeRate(
            sourceCurrency: $rate->sourceCurrency,
            targetCurrency: $rate->targetCurrency,
            rate: $rate->rate,
            provider: $rate->provider,
            fetchedAt: $rate->fetchedAt,
            expiresAt: $nextMarketOpenAt,
            isFallback: $rate->isFallback,
            metadata: array_merge($rate->metadata, [
                'market_opened_at' => $marketOpenedAt->toDateTimeString(),
                'market_next_open_at' => $nextMarketOpenAt->toDateTimeString(),
                'market_timezone' => $this->marketTimezone(),
            ]),
        );
    }

    private function cacheRate(string $cacheKey, CurrencyExchangeRate $rate, int $minutes): void
    {
        $this->cacheRateUntil($cacheKey, $rate, now()->addMinutes(max(1, $minutes)));
    }

    private function cacheRateUntil(string $cacheKey, CurrencyExchangeRate $rate, Carbon $expiresAt): void
    {
        Cache::put($cacheKey, [
            'source_currency' => $rate->sourceCurrency,
            'target_currency' => $rate->targetCurrency,
            'rate' => $rate->rate,
            'provider' => $rate->provider,
            'fetched_at' => $rate->fetchedAt->toDateTimeString(),
            'expires_at' => $rate->expiresAt?->toDateTimeString(),
            'is_fallback' => $rate->isFallback,
        ], $expiresAt);
    }

    private function cacheKey(string $sourceCurrency, string $targetCurrency): string
    {
        $marketOpenedAt = $this->currentMarketOpenedAt();

        return sprintf(
            'payments:exchange-rate:%s:%s:%s',
            $sourceCurrency,
            $targetCurrency,
            $marketOpenedAt->format('Y-m-d-H'),
        );
    }

    private function currentMarketOpenedAt(): Carbon
    {
        $now = $this->marketNow();
        $openingHour = $this->marketOpeningHour();

        $candidate = $now->copy()->setTime($openingHour, 0);

        if ($now->lt($candidate)) {
            $candidate->subDay();
        }

        while (! $this->isMarketOpeningDay($candidate)) {
            $candidate->subDay();
        }

        return $candidate;
    }

    private function nextMarketOpenAt(): Carbon
    {
        $now = $this->marketNow();
        $openingHour = $this->marketOpeningHour();

        $candidate = $now->copy()->setTime($openingHour, 0);

        if ($now->gte($candidate)) {
            $candidate->addDay();
        }

        while (! $this->isMarketOpeningDay($candidate)) {
            $candidate->addDay();
        }

        return $candidate;
    }

    private function isMarketOpeningDay(Carbon $date): bool
    {
        $openWeekdays = config('payments.currency_conversion.market.open_weekdays', [0, 1, 2, 3, 4]);

        return in_array($date->dayOfWeek, array_map('intval', $openWeekdays), true);
    }

    private function marketNow(): Carbon
    {
        return now($this->marketTimezone());
    }

    private function marketTimezone(): string
    {
        return (string) config('payments.currency_conversion.market.timezone', config('app.timezone', 'UTC'));
    }

    private function marketOpeningHour(): int
    {
        $hour = (int) config('payments.currency_conversion.market.opens_at_hour', 10);

        return max(0, min(23, $hour));
    }
}
