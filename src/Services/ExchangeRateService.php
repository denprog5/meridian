<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services;

use Denprog\Meridian\Contracts\ExchangeRateProvider;
use Denprog\Meridian\Models\Country;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Models\ExchangeRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ExchangeRateService
{
    protected ExchangeRateProvider $provider;

    protected string $configuredBaseCurrency;

    private ?string $activeCurrencyCode;

    private const string CACHE_KEY_PREFIX = 'exchange_rate_';

    public const string SESSION_KEY_ACTIVE_CURRENCY = 'meridian.active_currency';

    public function __construct(ExchangeRateProvider $provider)
    {
        $this->provider = $provider;
        $this->configuredBaseCurrency = Config::string('meridian.base_currency_code', 'USD');
        $this->activeCurrencyCode = $this->configuredBaseCurrency;
    }

    /**
     * Fetches and stores exchange rates from the configured provider.
     *
     * @param  string|null  $baseCurrency  The base currency code (e.g., 'USD'). Defaults to config.
     * @param  array<string>|null  $targetCurrencies  Array of target currency codes. Defaults to all available.
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
            Log::error("Failed to fetch rates from provider for base {$base} on {$dateString}.");

            return null;
        }

        foreach ($rates as $targetCurrencyCode => $rate) {
            $this->_storeRate($base, $targetCurrencyCode, $rate, $dateInstance);
        }

        Cache::forget(self::CACHE_KEY_PREFIX . "available_targets_{$base}_{$dateString}");

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
    public function convertAmount(
        float $amount,
        string $fromCurrencySymbolOrCode,
        string $toCurrencySymbolOrCode,
        string|Carbon|null $date = null
    ): ?float {
        if ($amount == 0) {
            return 0.0;
        }

        $fromCurrency = $this->_resolveCurrency($fromCurrencySymbolOrCode);
        $toCurrency = $this->_resolveCurrency($toCurrencySymbolOrCode);

        if (! $fromCurrency || ! $toCurrency) {
            Log::warning('Currency not resolved for conversion.', compact('fromCurrencySymbolOrCode', 'toCurrencySymbolOrCode'));

            return null;
        }

        if (! $fromCurrency->enabled || ! $toCurrency->enabled) {
            Log::warning('Attempted conversion with disabled currency.', ['from' => $fromCurrency->code, 'to' => $toCurrency->code]);

            return null;
        }

        $rate = $this->_getRate($fromCurrency->code, $toCurrency->code, $date);

        if ($rate === null) {
            Log::warning("Exchange rate not found for {$fromCurrency->code} to {$toCurrency->code} for date ".($date ? Carbon::parse($date)->toDateString() : 'latest').".");

            return null;
        }

        return round($amount * $rate, $toCurrency->decimal_places ?? 2);
    }

    /**
     * Gets the currently active currency code.
     * Reads from session if available and valid, otherwise defaults to base currency.
     */
    public function getActiveCurrency(): string
    {
        if (Session::has(self::SESSION_KEY_ACTIVE_CURRENCY)) {
            $sessionCurrencyCode = Session::get(self::SESSION_KEY_ACTIVE_CURRENCY);
            $currencyModel = $this->_resolveCurrency($sessionCurrencyCode);
            if ($currencyModel && $currencyModel->enabled) {
                $rateExists = ($sessionCurrencyCode === $this->configuredBaseCurrency) || ($this->_getRate($this->configuredBaseCurrency, $sessionCurrencyCode) !== null);
                if ($rateExists) {
                    $this->activeCurrencyCode = $sessionCurrencyCode;
                    return $this->activeCurrencyCode;
                }
            }
            Session::forget(self::SESSION_KEY_ACTIVE_CURRENCY);
        }
        $this->activeCurrencyCode = $this->configuredBaseCurrency;
        return $this->activeCurrencyCode;
    }

    /**
     * Sets the active currency by its code or symbol.
     * The currency must exist, be enabled, and have an exchange rate from the base currency.
     */
    public function setActiveCurrency(string $currencySymbolOrCode): bool
    {
        $currency = $this->_resolveCurrency($currencySymbolOrCode);

        if (! $currency || ! $currency->enabled) {
            Log::info('Attempt to set invalid or disabled currency as active.', ['input' => $currencySymbolOrCode]);

            return false;
        }

        if ($currency->code !== $this->configuredBaseCurrency && $this->_getRate($this->configuredBaseCurrency, $currency->code) === null) {
            Log::info('No exchange rate from base to target currency for setActiveCurrency.', ['base' => $this->configuredBaseCurrency, 'target' => $currency->code]);

            return false;
        }

        $this->activeCurrencyCode = $currency->code;
        Session::put(self::SESSION_KEY_ACTIVE_CURRENCY, $this->activeCurrencyCode);
        Log::info('Active currency set.', ['code' => $this->activeCurrencyCode]);

        return true;
    }

    /**
     * Sets the active currency based on a country's ISO alpha-2 code.
     * The country must exist, have an associated currency, and that currency must be settable as active.
     */
    public function setActiveCurrencyByCountry(string $countryIsoCode): bool
    {
        $country = Country::where('iso_alpha_2', strtoupper($countryIsoCode))->first();

        if (! $country || ! $country->currency_code) {
            Log::info('Country not found or has no currency code for setActiveCurrencyByCountry.', ['country_iso' => $countryIsoCode]);

            return false;
        }

        $result = $this->setActiveCurrency($country->currency_code);
        return $result;
    }

    /**
     * Converts an amount from the configured base currency to the active currency (or a specified target).
     *
     * @param  float  $amount  The amount in the base currency.
     * @param  string|null  $targetSymbolOrCode  Optional target currency code or symbol. If null, uses active currency.
     * @return float|null The converted amount or null on failure.
     */
    public function convert(float $amount, ?string $targetSymbolOrCode = null): ?float
    {
        $targetCurrencyCodeToUse = $this->configuredBaseCurrency;

        if ($targetSymbolOrCode !== null) {
            $resolvedTarget = $this->_resolveCurrency($targetSymbolOrCode);
            if (! $resolvedTarget || ! $resolvedTarget->enabled) {
                Log::warning('Target currency for convert() not resolvable or disabled.', ['target_input' => $targetSymbolOrCode]);
                return null;
            }
            $targetCurrencyCodeToUse = $resolvedTarget->code;
        } else {
            $targetCurrencyCodeToUse = $this->getActiveCurrency();
        }

        if ($this->configuredBaseCurrency === $targetCurrencyCodeToUse) {
            $targetForDecimals = $this->_resolveCurrency($targetCurrencyCodeToUse);
            return round($amount, $targetForDecimals->decimal_places ?? 2);
        }

        return $this->convertAmount($amount, $this->configuredBaseCurrency, $targetCurrencyCodeToUse);
    }

    /**
     * Retrieves a list of available target currency codes for a given base currency and date.
     *
     * @param  string|null  $baseCurrencyCode  The base currency code. Defaults to configured base currency.
     * @param  string|Carbon|null  $date  The date for which to check rates. Defaults to latest.
     * @return array<string> An array of target currency codes.
     */
    public function getAvailableTargetCurrencies(?string $baseCurrencyCode = null, string|Carbon|null $date = null): array
    {
        $base = $baseCurrencyCode ?? $this->configuredBaseCurrency;
        $dateInstance = ($date === null || $date === 'latest') ? Carbon::now() : Carbon::parse($date);
        $dateString = $dateInstance->toDateString();

        $cacheKey = self::CACHE_KEY_PREFIX . "available_targets_{$base}_{$dateString}";

        return Cache::remember($cacheKey, Carbon::now()->addHours(Config::get('meridian.cache_duration_hours', 24)), function () use ($base, $dateInstance) {
            return ExchangeRate::where('base_currency_code', $base)
                ->whereDate('date', $dateInstance->toDateString())
                ->join('currencies', 'exchange_rates.target_currency_code', '=', 'currencies.code')
                ->where('currencies.enabled', true)
                ->distinct()
                ->pluck('target_currency_code')
                ->all();
        });
    }

    /**
     * Retrieves the exchange rate between two currencies for a specific date.
     * It first checks the database, then falls back to the provider if not found for current/past dates.
     *
     * @param  string  $fromCurrencyCode  Currency code to convert from.
     * @param  string  $toCurrencyCode  Currency code to convert to.
     * @param  string|Carbon|null  $date  Date for the rate ('latest' or YYYY-MM-DD). Defaults to latest.
     * @return float|null The exchange rate, or null if not found.
     */
    private function _getRate(string $fromCurrencyCode, string $toCurrencyCode, string|Carbon|null $date = null): ?float
    {
        if ($fromCurrencyCode === $toCurrencyCode) {
            return 1.0;
        }

        $dateInstance = ($date === null || $date === 'latest') ? Carbon::now() : Carbon::parse($date);
        $dateString = $dateInstance->toDateString();
        $cacheKey = self::CACHE_KEY_PREFIX . "{$fromCurrencyCode}_{$toCurrencyCode}_{$dateString}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $rateFromDb = $this->_getRateFromDb($fromCurrencyCode, $toCurrencyCode, $dateInstance);
        if ($rateFromDb !== null) {
            Cache::put($cacheKey, $rateFromDb, Carbon::now()->addHours(Config::get('meridian.cache_duration_hours', 24)));

            return $rateFromDb;
        }

        if ($fromCurrencyCode === $this->configuredBaseCurrency && ! $dateInstance->isFuture()) {
            Log::info("Rate for {$fromCurrencyCode}->{$toCurrencyCode} on {$dateString} not in DB, attempting fetch from provider.");
            $ratesFromProvider = $this->fetchAndStoreRatesFromProvider($fromCurrencyCode, [$toCurrencyCode], $dateInstance);
            if (isset($ratesFromProvider[$toCurrencyCode])) {
                Cache::put($cacheKey, $ratesFromProvider[$toCurrencyCode], Carbon::now()->addHours(Config::get('meridian.cache_duration_hours', 24)));

                return $ratesFromProvider[$toCurrencyCode];
            }
        }

        if ($fromCurrencyCode !== $this->configuredBaseCurrency && $toCurrencyCode !== $this->configuredBaseCurrency) {
            $rateFromToBase = $this->_getRate($fromCurrencyCode, $this->configuredBaseCurrency, $dateInstance);
            $rateBaseToTarget = $this->_getRate($this->configuredBaseCurrency, $toCurrencyCode, $dateInstance);

            if ($rateFromToBase !== null && $rateBaseToTarget !== null && $rateFromToBase != 0) {
                $triangulatedRate = $rateBaseToTarget / $rateFromToBase;
                Cache::put($cacheKey, $triangulatedRate, Carbon::now()->addHours(Config::get('meridian.cache_duration_hours', 24)));

                return $triangulatedRate;
            }
        }

        Log::warning("Exchange rate not found and could not be fetched/triangulated for {$fromCurrencyCode} to {$toCurrencyCode} on {$dateString}.");

        return null;
    }

    /**
     * Stores an exchange rate in the database.
     */
    private function _storeRate(string $baseCurrencyCode, string $targetCurrencyCode, float $rate, Carbon $date): void
    {
        ExchangeRate::updateOrCreate(
            [
                'base_currency_code' => $baseCurrencyCode,
                'target_currency_code' => $targetCurrencyCode,
                'rate_date' => $date->toDateString(),
            ],
            ['rate' => $rate]
        );
    }

    /**
     * Retrieves an exchange rate directly from the database for a specific date.
     */
    private function _getRateFromDb(string $fromCurrencyCode, string $toCurrencyCode, Carbon $date): ?float
    {
        $exchangeRate = ExchangeRate::where('base_currency_code', $fromCurrencyCode)
            ->where('target_currency_code', $toCurrencyCode)
            ->whereDate('date', $date->toDateString())
            ->first();

        return $exchangeRate ? (float) $exchangeRate->rate : null;
    }

    /**
     * Resolves a currency code or symbol to a Currency model instance.
     * Prioritizes code match, then symbol match.
     */
    private function _resolveCurrency(string $symbolOrCode): ?Currency
    {
        $currency = Currency::where('code', strtoupper($symbolOrCode))->first();
        if ($currency) {
            return $currency;
        }

        return Currency::where('symbol', $symbolOrCode)->first();
    }
}
