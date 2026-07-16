<?php

namespace App\Repositories\Payments;

use App\DTOs\Payments\CurrencyExchangeRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CurrencyExchangeRateRepository
{
    public function store(CurrencyExchangeRate $rate): object
    {
        $effectiveDate = isset($rate->metadata['market_opened_at'])
            ? Carbon::parse($rate->metadata['market_opened_at'])->toDateString()
            : $rate->fetchedAt->toDateString();

        $id = DB::table('currency_exchange_rates')->insertGetId([
            'source_currency' => $rate->sourceCurrency,
            'target_currency' => $rate->targetCurrency,
            'rate' => $rate->rate,
            'provider' => $rate->provider,
            'effective_date' => $effectiveDate,
            'fetched_at' => $rate->fetchedAt,
            'expires_at' => $rate->expiresAt,
            'is_fallback' => $rate->isFallback,
            'metadata' => json_encode($rate->metadata, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('currency_exchange_rates')
            ->where('id', $id)
            ->first();
    }

    public function latestActiveRateForMarketWindow(
        string $sourceCurrency,
        string $targetCurrency,
        Carbon $marketOpenedAt,
    ): ?object {
        return DB::table('currency_exchange_rates')
            ->where('source_currency', strtolower($sourceCurrency))
            ->where('target_currency', strtolower($targetCurrency))
            ->where('effective_date', $marketOpenedAt->toDateString())
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('fetched_at')
            ->orderByDesc('id')
            ->first();
    }

    public function latestUsableRate(string $sourceCurrency, string $targetCurrency, int $maxStaleDays): ?object
    {
        $query = DB::table('currency_exchange_rates')
            ->where('source_currency', strtolower($sourceCurrency))
            ->where('target_currency', strtolower($targetCurrency));

        if ($maxStaleDays > 0) {
            $query->where('fetched_at', '>=', now()->subDays($maxStaleDays));
        }

        return $query
            ->orderByDesc('fetched_at')
            ->orderByDesc('id')
            ->first();
    }

    public function toDto(object $rate, bool $isFallback = true): CurrencyExchangeRate
    {
        return new CurrencyExchangeRate(
            sourceCurrency: (string) $rate->source_currency,
            targetCurrency: (string) $rate->target_currency,
            rate: (float) $rate->rate,
            provider: (string) $rate->provider,
            fetchedAt: Carbon::parse($rate->fetched_at),
            expiresAt: $rate->expires_at ? Carbon::parse($rate->expires_at) : null,
            isFallback: $isFallback,
            metadata: [
                'stored_rate_id' => (int) $rate->id,
            ],
        );
    }
}
