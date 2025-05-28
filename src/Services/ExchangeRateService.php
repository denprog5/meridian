<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services;

use Denprog\Meridian\Contracts\ExchangeRateProvider;
use Denprog\Meridian\Contracts\ExchangeRateServiceContract;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Models\ExchangeRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

final readonly class ExchangeRateService implements ExchangeRateServiceContract
{
    private const string CACHE_KEY_PREFIX = 'exchange_rate_';

    private string $configuredBaseCurrency;

    public function __construct(private ExchangeRateProvider $provider)
    {
        $this->configuredBaseCurrency = Config::string('meridian.base_currency_code', 'USD');
    }

    /**
     * Fetches and stores exchange rates from the configured provider.
     *
     * @param  string|null  $baseCurrency  The base currency code (e.g., 'USD'). Defaults to config.
     * @param  array<string>|null  $targetCurrencies  Array of target currency codes. Defaults to all available from provider for the base.
     * @param  string|Carbon|null  $date  Date for rates ('latest' or YYYY-MM-DD). Defaults to 'latest'.
     * @return array<string, float>|null An array of currency codes to rates, or null on failure.
     */
    public function fetchAndStoreRatesFromProvider(
        ?string $baseCurrency = null,
        ?array $targetCurrencies = null,
        string|Carbon|null $date = null
    ): ?array {
        $base = $baseCurrency ?? $this->configuredBaseCurrency;
        $dateInstance = ($date === null || $date === 'latest') ? Carbon::now() : Carbon::parse($date);
        $dateString = $dateInstance->toDateString();

        $rates = $this->provider->getRates($base, $targetCurrencies, $dateInstance);

        if ($rates === null) {
            Log::error("Failed to fetch rates from provider for base $base on $dateString.");

            return null;
        }

        foreach ($rates as $targetCurrencyCode => $rate) {
            $this->_storeRate($base, $targetCurrencyCode, $rate, $dateInstance);
        }

        Cache::forget(self::CACHE_KEY_PREFIX."available_targets_{$base}_$dateString");

        return $rates;
    }

    /**
     * Converts an amount from one currency to another.
     *
     * @param  float  $amount  The amount to convert.
     * @param  string  $fromCurrencySymbolOrCode  The currency code or symbol to convert from.
     * @param  string  $toCurrencySymbolOrCode  The currency code or symbol to convert to.
     * @param  string|Carbon|null  $date  The date for the exchange rate. Defaults to latest.
     * @return float|null The converted amount, or null if conversion is not possible.
     */
    public function convert(
        float $amount,
        string $fromCurrencySymbolOrCode,
        string $toCurrencySymbolOrCode,
        string|Carbon|null $date = null
    ): ?float {
        $fromCurrency = $this->_resolveCurrency($fromCurrencySymbolOrCode);
        $toCurrency = $this->_resolveCurrency($toCurrencySymbolOrCode);

        if (! $fromCurrency instanceof Currency || ! $toCurrency instanceof Currency) {
            Log::warning('Currency not resolved for conversion.', ['fromCurrencySymbolOrCode' => $fromCurrencySymbolOrCode, 'toCurrencySymbolOrCode' => $toCurrencySymbolOrCode]);

            return null;
        }

        if (! $fromCurrency->enabled || ! $toCurrency->enabled) {
            Log::warning('Attempted conversion with disabled currency.', ['from' => $fromCurrency->code, 'to' => $toCurrency->code]);

            return null;
        }

        $rate = $this->getExchangeRate($fromCurrency->code, $toCurrency->code, $date);

        if ($rate === null) {
            Log::warning("Exchange rate not found for $fromCurrency->code to $toCurrency->code for date ".($date ? Carbon::parse($date)->toDateString() : 'latest').'.');

            return null;
        }

        return round($amount * $rate, $toCurrency->decimal_places ?? 2);
    }

    /**
     * Retrieves available target currency codes for a given base currency and date.
     *
     * @param  string  $baseCurrencyCode  The base currency code.
     * @param  Carbon|null  $date  The specific date for rates, or null for the latest rates.
     * @return array<string> An array of target currency codes.
     */
    public function getAvailableTargetCurrencies(string $baseCurrencyCode, ?Carbon $date = null): array
    {
        $base = $baseCurrencyCode;
        $dateInstance = $date ?? Carbon::now();
        $dateString = $dateInstance->toDateString();

        $cacheKey = self::CACHE_KEY_PREFIX."available_targets_{$base}_$dateString";

        /** @var array<string> */
        return Cache::rememberForever($cacheKey, fn () => ExchangeRate::query()->where('base_currency_code', $base)
            ->where('rate_date', $dateInstance->toDateString())
            ->join('currencies', 'exchange_rates.target_currency_code', '=', 'currencies.code')
            ->where('currencies.enabled', true)
            ->distinct()
            ->pluck('target_currency_code')
            ->all());
    }

    /**
     * Retrieves exchange rates for multiple target currencies against a single base currency.
     *
     * @param  string  $baseCurrencyCode  The base currency code.
     * @param  array<string>  $targetCurrencyCodes  An array of target currency codes.
     * @param  string|Carbon|null  $date  The date for the exchange rates ('latest', 'YYYY-MM-DD', or Carbon instance). Defaults to latest.
     * @return array<string, float> An associative array of target currency codes to their rates. Rates that cannot be found are omitted.
     */
    public function getExchangeRates(string $baseCurrencyCode, array $targetCurrencyCodes, string|Carbon|null $date = null): array
    {
        $rates = [];
        $dateInstance = ($date === null || $date === 'latest') ? Carbon::now() : Carbon::parse($date);

        foreach ($targetCurrencyCodes as $targetCurrencyCode) {
            $rate = $this->getExchangeRate($baseCurrencyCode, $targetCurrencyCode, $dateInstance);
            if ($rate !== null) {
                $rates[$targetCurrencyCode] = $rate;
            }
        }

        return $rates;
    }

    /**
     * Retrieves an exchange rate.
     * Tries direct, inverse, then via the configured base currency if necessary.
     *
     * @param  string  $fromCurrencyCode  The currency code to convert from.
     * @param  string  $toCurrencyCode  The currency code to convert to.
     * @param  string|Carbon|null  $date  The date for the exchange rate ('latest', 'YYYY-MM-DD', or Carbon instance). Defaults to latest.
     * @return float|null The exchange rate, or null if not found/calculable.
     */
    public function getExchangeRate(string $fromCurrencyCode, string $toCurrencyCode, string|Carbon|null $date = null): ?float
    {
        if ($fromCurrencyCode === $toCurrencyCode) {
            return 1.0;
        }

        $dateInstance = ($date === null || $date === 'latest') ? Carbon::now() : Carbon::parse($date);
        $rateDateString = $dateInstance->toDateString();

        $cacheKey = self::CACHE_KEY_PREFIX."{$fromCurrencyCode}_{$toCurrencyCode}_$rateDateString";

        if (Cache::has($cacheKey)) {
            /** @var float|null */
            return Cache::get($cacheKey);
        }

        /** @var float|null $rate */
        $rate = ExchangeRate::query()->where('base_currency_code', $fromCurrencyCode)
            ->where('target_currency_code', $toCurrencyCode)
            ->where('rate_date', $rateDateString)
            ->value('rate');

        if ($rate !== null) {
            Cache::put($cacheKey, $rate, Carbon::now()->addDays(Config::integer('meridian.cache_duration_days.exchange_rates', 7)));

            return $rate;
        }

        /** @var float|null $inverseRate */
        $inverseRate = ExchangeRate::query()->where('base_currency_code', $toCurrencyCode)
            ->where('target_currency_code', $fromCurrencyCode)
            ->where('rate_date', $rateDateString)
            ->value('rate');

        if (! empty($inverseRate)) {
            $calculatedRate = 1 / $inverseRate;
            Cache::put($cacheKey, $calculatedRate, Carbon::now()->addDays(Config::integer('meridian.cache_duration_days.exchange_rates', 7)));

            return $calculatedRate;
        }

        // Try converting via the configured base currency (e.g., USD)
        $configuredBase = $this->configuredBaseCurrency;
        if ($fromCurrencyCode !== $configuredBase && $toCurrencyCode !== $configuredBase) {
            // Avoid infinite recursion if configuredBase is one of the from/to currencies in a failed lookup chain
            $rateFromBaseToSource = $this->getExchangeRate($configuredBase, $fromCurrencyCode, $dateInstance);
            $rateFromBaseToTarget = $this->getExchangeRate($configuredBase, $toCurrencyCode, $dateInstance);

            if (! empty($rateFromBaseToSource) && ! empty($rateFromBaseToTarget)) {
                $calculatedRate = $rateFromBaseToTarget / $rateFromBaseToSource;
                Cache::put($cacheKey, $calculatedRate, Carbon::now()->addDays(Config::integer('meridian.cache_duration_days.exchange_rates', 7)));

                return $calculatedRate;
            }
        }

        Log::debug("Exchange rate not found or calculable for $fromCurrencyCode to $toCurrencyCode on $rateDateString.");

        return null;
    }

    /**
     * Stores or updates an exchange rate in the database and cache.
     */
    private function _storeRate(string $baseCurrencyCode, string $targetCurrencyCode, float $rate, Carbon $date): void
    {
        $rateDateString = $date->toDateString();

        ExchangeRate::query()->updateOrCreate(
            [
                'base_currency_code' => $baseCurrencyCode,
                'target_currency_code' => $targetCurrencyCode,
                'rate_date' => $rateDateString,
            ],
            ['rate' => $rate]
        );

        $cacheKey = self::CACHE_KEY_PREFIX."{$baseCurrencyCode}_{$targetCurrencyCode}_$rateDateString";
        Cache::put($cacheKey, $rate, Carbon::now()->addDays(Config::integer('meridian.cache_duration_days.exchange_rates', 7)));

        $inverseCacheKey = self::CACHE_KEY_PREFIX."{$targetCurrencyCode}_{$baseCurrencyCode}_$rateDateString";
        Cache::forget($inverseCacheKey);
    }

    /**
     * Resolves a currency code or symbol to a Currency model.
     * Ensures the currency is enabled.
     */
    private function _resolveCurrency(string $symbolOrCode): ?Currency
    {
        $cacheKey = 'currency_resolve_'.md5(mb_strtolower($symbolOrCode));

        /** @var Currency|null */
        return Cache::rememberForever($cacheKey, function () use ($symbolOrCode) {
            $query = Currency::query()->where('enabled', true);

            if (mb_strlen($symbolOrCode) === 3) {
                $query->where('code', mb_strtoupper($symbolOrCode));
            } else {
                $query->where('symbol', $symbolOrCode);
            }

            $currency = $query->first();
            if ($currency) {
                return $currency;
            }

            return null;
        });
    }
}
