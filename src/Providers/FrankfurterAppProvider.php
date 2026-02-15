<?php

declare(strict_types=1);

namespace Denprog\Meridian\Providers;

use Denprog\Meridian\Contracts\ExchangeRateProvider;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FrankfurterAppProvider implements ExchangeRateProvider
{
    private const string DEFAULT_API_BASE_URL = 'https://api.frankfurter.dev/v1';

    /**
     * Get exchange rates from a base currency to target currencies for a specific date.
     *
     * @param  string  $baseCurrencyCode  The base currency code (e.g., 'USD').
     * @param  array<string>|null  $targetCurrencyCodes  An array of target currency codes (e.g., ['EUR', 'GBP']). If null, the API might return all available.
     * @param  Carbon|null  $date  The date for which to fetch rates. Defaults to latest if null.
     * @return array<string, float>|null An associative array of target currency codes to rates, or null on failure.
     */
    public function getRates(string $baseCurrencyCode, ?array $targetCurrencyCodes = null, ?Carbon $date = null): ?array
    {
        $endpoint = $date instanceof Carbon ? $date->toDateString() : 'latest';
        $url = mb_rtrim($this->apiBaseUrl(), '/')."/$endpoint";

        $queryParams = ['from' => mb_strtoupper($baseCurrencyCode)];

        if ($targetCurrencyCodes !== null && $targetCurrencyCodes !== []) {
            $normalizedTargets = array_values(array_filter(
                $targetCurrencyCodes,
                static fn (string $code): bool => $code !== ''
            ));

            if ($normalizedTargets !== []) {
                $queryParams['to'] = implode(',', array_map(
                    static fn (string $code): string => mb_strtoupper($code),
                    $normalizedTargets
                ));
            }
        }

        try {
            /** @var Response $response */
            $response = Http::timeout(config()->integer('meridian.http_timeout', 10))
                ->get($url, $queryParams);

            if ($response->failed()) {
                Log::error(
                    'Frankfurter.app API request failed.',
                    [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'url' => $url,
                        'params' => $queryParams,
                    ]
                );

                return null;
            }

            /** @var array<string, mixed>|null $data */
            $data = $response->json();

            if (! is_array($data) || ! isset($data['rates']) || ! is_array($data['rates'])) {
                Log::error('Frankfurter.app API response is invalid.', [
                    'url' => $url,
                    'params' => $queryParams,
                    'body' => $response->body(),
                ]);

                return null;
            }

            $rates = $this->normalizeRates($data['rates']);

            if ($rates === []) {
                Log::error('Frankfurter.app API returned empty or invalid rates payload.', [
                    'url' => $url,
                    'params' => $queryParams,
                    'rates' => $data['rates'],
                ]);

                return null;
            }

            return $rates;

        } catch (Throwable $e) {
            Log::error(
                'Exception while fetching rates from Frankfurter.app.',
                ['exception' => $e, 'base_currency' => $baseCurrencyCode, 'target_currencies' => $targetCurrencyCodes]
            );

            return null;
        }
    }

    private function apiBaseUrl(): string
    {
        return config()->string('meridian.exchange_rates.providers.frankfurter.api_url', self::DEFAULT_API_BASE_URL);
    }

    /**
     * @param  array<mixed>  $rawRates
     * @return array<string, float>
     */
    private function normalizeRates(array $rawRates): array
    {
        $rates = [];

        foreach ($rawRates as $currencyCode => $rate) {
            if (! is_string($currencyCode) || ! is_numeric($rate)) {
                continue;
            }

            $rates[mb_strtoupper($currencyCode)] = (float) $rate;
        }

        return $rates;
    }
}
