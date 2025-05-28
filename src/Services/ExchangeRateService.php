<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services;

use Denprog\Meridian\Contracts\CurrencyServiceContract;
use Denprog\Meridian\Contracts\ExchangeRateProvider; // This is the ExchangeRateProviderContract
use Denprog\Meridian\Contracts\ExchangeRateServiceContract;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Models\ExchangeRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use LogicException;
use NumberFormatter;

// For currency formatting in later steps

final class ExchangeRateService implements ExchangeRateServiceContract // Removed readonly from class
{
    private const string CACHE_KEY_PREFIX = 'exchange_rate_';

    // Configuration
    private readonly string $systemBaseCurrencyCode;

    // Lazily loaded properties for performance
    private ?Currency $systemBaseCurrencyModel = null;

    private ?Currency $activeDisplayCurrencyModel = null;

    private ?float $activeDisplayCurrencyRate = null; // Stores rate from systemBase to activeDisplay

    public function __construct(
        private readonly CurrencyServiceContract $currencyService,
        private readonly ExchangeRateProvider $exchangeRateProvider
    ) {
        $this->systemBaseCurrencyCode = Config::string('meridian.system_base_currency_code', 'USD');
    }

    /**
     * {@inheritdoc}
     */
    public function getSystemBaseCurrency(): Currency
    {
        if ($this->systemBaseCurrencyModel instanceof Currency) {
            return $this->systemBaseCurrencyModel;
        }

        $currency = $this->currencyService->findByCode($this->systemBaseCurrencyCode);

        if (! $currency instanceof Currency) {
            throw new LogicException("System base currency '$this->systemBaseCurrencyCode' not found in database.");
        }

        if (! $currency->enabled) {
            throw new LogicException("System base currency '$this->systemBaseCurrencyCode' is disabled.");
        }

        return $this->systemBaseCurrencyModel = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveDisplayCurrency(): Currency
    {
        if ($this->activeDisplayCurrencyModel instanceof Currency) {
            return $this->activeDisplayCurrencyModel;
        }

        // The CurrencyService->getActiveDisplayCurrency() is expected to always return a valid, enabled Currency model
        // (defaulting to system base if no specific user/session currency is set and enabled).
        return $this->activeDisplayCurrencyModel = $this->currencyService->get();
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
        $base = $baseCurrency ?? $this->systemBaseCurrencyCode;
        $dateInstance = ($date === null || $date === 'latest') ? Carbon::now() : Carbon::parse($date);
        $dateString = $dateInstance->toDateString();

        $rates = $this->exchangeRateProvider->getRates($base, $targetCurrencies, $dateInstance);

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
     * {@inheritdoc}
     */
    public function convert(float $amountInSystemBase, bool $withUnit = false, ?string $locale = null): string|float|null
    {
        $activeDisplayCurrency = $this->getActiveDisplayCurrency();
        $systemBaseCurrency = $this->getSystemBaseCurrency();

        $convertedAmount = $amountInSystemBase;

        if ($activeDisplayCurrency->code !== $systemBaseCurrency->code) {
            $rate = $this->_getRateToActiveDisplayCurrency();
            if ($rate === null) {
                Log::warning('Failed to get exchange rate for active display currency conversion.', [
                    'system_base' => $systemBaseCurrency->code,
                    'active_display' => $activeDisplayCurrency->code,
                ]);

                return null;
            }
            $convertedAmount = $amountInSystemBase * $rate;
        }

        if ($withUnit) {
            return $this->_formatAmount($convertedAmount, $activeDisplayCurrency, $locale);
        }

        return round($convertedAmount, $activeDisplayCurrency->decimal_places ?? Config::integer('meridian.formatting.default_decimal_places', 2));
    }

    /**
     * {@inheritdoc}
     */
    public function convertBetween(float $amount, string $fromCurrencyCode, string $toCurrencyCode, bool $withUnit = false, ?string $locale = null, string|Carbon|null $date = null): string|float|null
    {
        if ($fromCurrencyCode === $toCurrencyCode) {
            $toCurrency = $this->_resolveCurrency($toCurrencyCode);
            if (! $toCurrency instanceof Currency) {
                Log::warning('Currency not resolved for same-currency conversion formatting.', ['code' => $toCurrencyCode]);

                // Depending on strictness, could return null or unformatted amount.
                // Returning unformatted amount if currency cannot be resolved for formatting.
                return $amount;
            }
            if ($withUnit) {
                return $this->_formatAmount($amount, $toCurrency, $locale);
            }

            return round($amount, $toCurrency->decimal_places ?? Config::integer('meridian.formatting.default_decimal_places', 2));
        }

        $fromCurrency = $this->_resolveCurrency($fromCurrencyCode);
        $toCurrency = $this->_resolveCurrency($toCurrencyCode);

        if (! $fromCurrency instanceof Currency || ! $toCurrency instanceof Currency) {
            Log::warning('Currency not resolved for conversion.', [
                'from_code' => $fromCurrencyCode,
                'to_code' => $toCurrencyCode,
                'from_resolved' => $fromCurrency instanceof Currency,
                'to_resolved' => $toCurrency instanceof Currency,
            ]);

            return null;
        }

        if (! $fromCurrency->enabled || ! $toCurrency->enabled) {
            Log::warning('Attempted conversion with disabled currency.', [
                'from_currency' => $fromCurrency->code,
                'from_enabled' => $fromCurrency->enabled,
                'to_currency' => $toCurrency->code,
                'to_enabled' => $toCurrency->enabled,
            ]);

            return null;
        }

        $rate = $this->getExchangeRate($fromCurrency->code, $toCurrency->code, $date);

        if ($rate === null) {
            $dateString = ($date instanceof Carbon) ? $date->toDateString() : ($date ?? 'latest');
            Log::warning("Exchange rate not found for $fromCurrency->code to $toCurrency->code for date $dateString.");

            return null;
        }

        $convertedAmount = $amount * $rate;

        if ($withUnit) {
            return $this->_formatAmount($convertedAmount, $toCurrency, $locale);
        }

        return round($convertedAmount, $toCurrency->decimal_places ?? Config::integer('meridian.formatting.default_decimal_places', 2));
    }

    /**
     * Retrieves available target currency codes for a given base currency and date.
     *
     * @param  string  $baseCurrencyCode  The base currency code.
     * @param  Carbon|string|null  $date  The specific date for rates (Carbon, 'YYYY-MM-DD', or 'latest'), or null for the latest rates.
     * @return array<int, string> An array of target currency codes.
     */
    public function getAvailableTargetCurrencies(string $baseCurrencyCode, Carbon|string|null $date = null): array
    {
        $base = $baseCurrencyCode;
        $dateInstance = ($date === null || $date === 'latest') ? Carbon::now() : ($date instanceof Carbon ? $date : Carbon::parse($date));
        $dateString = $dateInstance->toDateString();

        $cacheKey = self::CACHE_KEY_PREFIX."available_targets_{$base}_$dateString";

        /** @var array<int,string> */
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

        // Try converting via the system's base currency (e.g., USD)
        $systemBase = $this->systemBaseCurrencyCode;
        if ($fromCurrencyCode !== $systemBase && $toCurrencyCode !== $systemBase) {
            // Avoid infinite recursion if systemBase is one of the from/to currencies in a failed lookup chain
            $fromRate = $this->getExchangeRate($systemBase, $fromCurrencyCode, $dateInstance);
            $toRate = $this->getExchangeRate($systemBase, $toCurrencyCode, $dateInstance);

            if (! empty($fromRate) && ! empty($toRate)) {
                $calculatedRate = $toRate / $fromRate;
                Cache::put($cacheKey, $calculatedRate, Carbon::now()->addDays(Config::integer('meridian.cache_duration_days.exchange_rates', 7)));

                return $calculatedRate;
            }
        }

        Log::debug("Exchange rate not found or calculable for $fromCurrencyCode to $toCurrencyCode on $rateDateString.");

        return null;
    }

    /**
     * Helper to get the exchange rate from the system base currency to the active display currency.
     *
     * @return float|null The exchange rate, or null if not found.
     */
    private function _getRateToActiveDisplayCurrency(): ?float
    {
        if ($this->activeDisplayCurrencyRate !== null) {
            return $this->activeDisplayCurrencyRate;
        }

        $systemBase = $this->getSystemBaseCurrency();
        $activeDisplay = $this->getActiveDisplayCurrency();

        if ($systemBase->code === $activeDisplay->code) {
            $this->activeDisplayCurrencyRate = 1.0;

            return 1.0;
        }

        // Use 'latest' as we are dealing with current display context
        $rate = $this->getExchangeRate($systemBase->code, $activeDisplay->code, 'latest');

        if ($rate === null) {
            Log::error('Critical: Could not retrieve exchange rate between system base and active display currency.', [
                'from' => $systemBase->code,
                'to' => $activeDisplay->code,
            ]);

            return null;
        }

        return $this->activeDisplayCurrencyRate = $rate;
    }

    /**
     * Formats an amount with the given currency's symbol and decimal places.
     *
     * @param  float  $amount  The amount to format.
     * @param  Currency  $currency  The currency model for formatting rules.
     * @param  string|null  $locale  Optional locale for formatting (e.g., 'en_US', 'de_DE'). Defaults to app locale.
     * @return string The formatted currency string.
     */
    private function _formatAmount(float $amount, Currency $currency, ?string $locale = null): string
    {
        $currentLocale = $locale ?? Config::string('app.locale', 'en_US');
        $formatter = new NumberFormatter($currentLocale, NumberFormatter::CURRENCY);
        // Ensure we use the currency's specific decimal places if available
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $currency->decimal_places ?? Config::integer('meridian.formatting.default_decimal_places', 2));

        // Attempt to format with the currency code. If it fails (e.g. due to locale not supporting the code directly in symbol position),
        // fallback to a more generic formatting or append the code.
        $formatted = $formatter->formatCurrency($amount, $currency->code);

        if ($formatted === false) {
            // Fallback for robust formatting if formatCurrency fails for some locales/currency code combos
            $formatter = new NumberFormatter($currentLocale, NumberFormatter::DECIMAL);
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $currency->decimal_places ?? Config::integer('meridian.formatting.default_decimal_places', 2));
            $formattedAmount = $formatter->format($amount) ?: (string) $amount;

            // Determine if symbol should be prefixed or suffix based on typical conventions or config
            // This is a simplified heuristic; true localization is complex.
            // For now, just append code if symbol is not already present from a failed formatCurrency.
            return $currency->symbol.' '.$formattedAmount; // Or $formattedAmount . ' ' . $currency->code;
        }

        return $formatted;
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
