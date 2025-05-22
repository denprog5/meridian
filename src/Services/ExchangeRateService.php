<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services;

use Carbon\Carbon;
use Denprog\Meridian\Models\ExchangeRate;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    protected string $frankfurterApiBaseUrl;

    protected string $configuredBaseCurrency;

    public function __construct()
    {
        $this->frankfurterApiBaseUrl = Config::string('meridian.exchange_rate_providers.frankfurter.api_url', 'https://api.frankfurter.app');
        $this->configuredBaseCurrency = Config::string('meridian.base_currency_code', 'USD');
    }

    /**
     * Fetches exchange rates from Frankfurter.app for the configured base currency
     * and stores them in the database.
     *
     * @return array{success: bool, message: string, fetched_at?: string, rates_processed?: int, base_currency?: string}
     */
    public function fetchAndStoreRatesFromFrankfurter(): array
    {
        $baseCurrency = $this->configuredBaseCurrency;
        $url = "$this->frankfurterApiBaseUrl/latest?from=$baseCurrency";

        try {
            $response = Http::timeout(10)->get($url);

            if (! $response->successful()) {
                return ['success' => false, 'message' => 'Failed to fetch rates from Frankfurter.app. HTTP status: '.$response->status()];
            }

            /** @var array{base: string, date: string, rates: array<string, float>} $data */
            $data = $response->json();

            $rateDate = Carbon::parse($data['date'])->toDateString();
            $rates = $data['rates'];
            $processedCount = 0;

            foreach ($rates as $targetCurrencyCode => $rate) {
                if ($targetCurrencyCode === $baseCurrency) {
                    continue;
                }

                ExchangeRate::query()->updateOrCreate(
                    [
                        'base_currency_code' => $baseCurrency,
                        'target_currency_code' => $targetCurrencyCode,
                        'rate_date' => $rateDate,
                    ],
                    ['rate' => $rate]
                );
                $processedCount++;
            }

            return [
                'success' => true,
                'message' => 'Exchange rates fetched and stored successfully.',
                'fetched_at' => $rateDate,
                'rates_processed' => $processedCount,
                'base_currency' => $baseCurrency,
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'An unexpected error occurred: '.$e->getMessage()];
        }
    }

    /**
     * Converts an amount from one currency to another using stored exchange rates.
     *
     * @param  float  $amount  The amount to convert.
     * @param  string  $fromCurrencyCode  The ISO code of the currency to convert from.
     * @param  string  $toCurrencyCode  The ISO code of the currency to convert to.
     * @param  string|Carbon|null  $date  The date for which to get the exchange rate. Defaults to the latest available.
     * @return float|null The converted amount, or null if conversion is not possible (e.g., rate not found).
     */
    public function convertAmount(float $amount, string $fromCurrencyCode, string $toCurrencyCode, string|Carbon|null $date = null): ?float
    {
        if ($fromCurrencyCode === $toCurrencyCode) {
            return $amount;
        }

        $base = $this->configuredBaseCurrency;
        $carbonDate = $date ? ($date instanceof Carbon ? $date : Carbon::parse($date)) : null;

        // Case 1: From base currency to another currency
        if ($fromCurrencyCode === $base) {
            $rate = $this->_getRate($base, $toCurrencyCode, $carbonDate);

            return $rate ? $amount * $rate : null;
        }

        // Case 2: From another currency to base currency
        if ($toCurrencyCode === $base) {
            $rate = $this->_getRate($base, $fromCurrencyCode, $carbonDate);

            return $rate ? $amount / $rate : null;
        }

        // Case 3: From one non-base currency to another non-base currency
        $rateFromBaseToSource = $this->_getRate($base, $fromCurrencyCode, $carbonDate);
        $rateFromBaseToTarget = $this->_getRate($base, $toCurrencyCode, $carbonDate);

        if ($rateFromBaseToSource && $rateFromBaseToTarget) {
            $amountInBase = $amount / $rateFromBaseToSource;

            return $amountInBase * $rateFromBaseToTarget;
        }

        return null;
    }

    /**
     * Get a list of available target currency codes for which exchange rates exist.
     *
     * @param  string|null  $baseCurrencyCode  The base currency to check against. Defaults to configured base.
     * @param  string|Carbon|null  $date  The specific date to check for rates. Defaults to any latest available.
     * @return array<int, string> An array of target currency codes.
     */
    public function getAvailableTargetCurrencies(?string $baseCurrencyCode = null, string|Carbon|null $date = null): array
    {
        $actualBaseCurrency = $baseCurrencyCode ?? $this->configuredBaseCurrency;
        $carbonDate = $date ? ($date instanceof Carbon ? $date : Carbon::parse($date)) : null;

        $query = ExchangeRate::query()
            ->where('base_currency_code', $actualBaseCurrency)
            ->select('target_currency_code')
            ->distinct();

        if ($carbonDate instanceof Carbon) {
            $query->whereDate('rate_date', $carbonDate->toDateString());
        }

        /** @var array<int, string> */
        return $query->pluck('target_currency_code')->toArray();
    }

    /**
     * Retrieves a specific exchange rate from the database.
     *
     * @param  Carbon|null  $date  If null, gets the latest rate. Otherwise, gets rate for the specific date.
     * @return float|null The exchange rate, or null if not found.
     */
    private function _getRate(string $baseCode, string $targetCode, ?Carbon $date = null): ?float
    {
        $query = ExchangeRate::query()->where('base_currency_code', $baseCode)
            ->where('target_currency_code', $targetCode);

        if ($date instanceof Carbon) {
            $query->whereDate('rate_date', $date->toDateString());
        } else {
            $query->orderBy('rate_date', 'desc');
        }

        $exchangeRate = $query->first();

        return $exchangeRate->rate ?? null;
    }
}
