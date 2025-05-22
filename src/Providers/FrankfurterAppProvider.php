<?php

declare(strict_types=1);

namespace Denprog\Meridian\Providers;

use Denprog\Meridian\Contracts\ExchangeRateProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FrankfurterAppProvider implements ExchangeRateProvider
{
    private const string API_BASE_URL = 'https://api.frankfurter.dev/v1';

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
        $url = self::API_BASE_URL."/$endpoint";

        $queryParams = ['from' => $baseCurrencyCode];
        if ($targetCurrencyCodes !== null && $targetCurrencyCodes !== []) {
            $queryParams['to'] = implode(',', $targetCurrencyCodes);
        }

        try {
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

            /** @var array{base: string, date: string, rates: array<string, float>} $data */
            $data = $response->json();

            return array_map(fn ($rate): float => (float) $rate, $data['rates']);

        } catch (Throwable $e) {
            Log::error(
                'Exception while fetching rates from Frankfurter.app.',
                ['exception' => $e->getMessage(), 'base_currency' => $baseCurrencyCode, 'target_currencies' => $targetCurrencyCodes]
            );

            return null;
        }
    }
}
