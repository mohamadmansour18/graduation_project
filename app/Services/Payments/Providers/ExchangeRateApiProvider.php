<?php

namespace App\Services\Payments\Providers;

use App\Contracts\Payments\ExchangeRateProviderInterface;
use App\DTOs\Payments\CurrencyExchangeRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExchangeRateApiProvider implements ExchangeRateProviderInterface
{
    public function getRate(string $sourceCurrency, string $targetCurrency): CurrencyExchangeRate
    {
        $apiKey = (string) config('payments.currency_conversion.providers.exchange_rate_api.key');

        if ($apiKey === '') {
            throw new RuntimeException('ExchangeRate-API key is not configured.');
        }

        $baseUrl = rtrim((string) config('payments.currency_conversion.providers.exchange_rate_api.base_url'), '/');
        $timeout = (int) config('payments.currency_conversion.timeout_seconds', 3);
        $connectTimeout = (int) config('payments.currency_conversion.connect_timeout_seconds', 3);

        $response = Http::connectTimeout(max(1, $connectTimeout))
            ->timeout(max(1, $timeout))
            ->acceptJson()
            ->get(sprintf(
                '%s/%s/pair/%s/%s',
                $baseUrl,
                $apiKey,
                strtoupper($sourceCurrency),
                strtoupper($targetCurrency),
            ));

        if (! $response->ok()) {
            throw new RuntimeException('Exchange rate provider request failed.');
        }

        $payload = $response->json();

        if (($payload['result'] ?? null) !== 'success' || ! isset($payload['conversion_rate'])) {
            throw new RuntimeException('Exchange rate provider returned an invalid response.');
        }

        $rate = (float) $payload['conversion_rate'];

        if ($rate <= 0) {
            throw new RuntimeException('Exchange rate provider returned an invalid rate.');
        }

        $fetchedAt = now();

        return new CurrencyExchangeRate(
            sourceCurrency: strtolower($sourceCurrency),
            targetCurrency: strtolower($targetCurrency),
            rate: $rate,
            provider: 'exchange_rate_api',
            fetchedAt: $fetchedAt,
            expiresAt: $this->expiresAt($payload, $fetchedAt),
            isFallback: false,
            metadata: [
                'time_last_update_unix' => $payload['time_last_update_unix'] ?? null,
                'time_next_update_unix' => $payload['time_next_update_unix'] ?? null,
            ],
        );
    }

    private function expiresAt(array $payload, Carbon $fallbackFrom): Carbon
    {
        if (isset($payload['time_next_update_unix']) && is_numeric($payload['time_next_update_unix'])) {
            return Carbon::createFromTimestamp((int) $payload['time_next_update_unix']);
        }

        return $fallbackFrom->copy()->addDay();
    }
}
