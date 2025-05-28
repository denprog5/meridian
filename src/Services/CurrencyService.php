<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services;

use Denprog\Meridian\Contracts\CurrencyServiceContract;
use Denprog\Meridian\Models\Currency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Service class for handling currency-related operations.
 *
 * This service provides methods to retrieve and manage currency data,
 * with built-in caching support for improved performance.
 */
final class CurrencyService implements CurrencyServiceContract
{
    public const string SESSION_CURRENCY_CODE = CurrencyServiceContract::SESSION_CURRENCY_CODE;

    /** @var string[] */
    protected array $configuredActiveCurrencyCodes = [];

    private ?Currency $currency = null;

    private ?Currency $baseCurrency = null;

    public function __construct()
    {
        /** @var string[]|null $activeCodes */
        $activeCodes = Config::get('meridian.active_currencies');
        if (! empty($activeCodes)) {
            $this->configuredActiveCurrencyCodes = array_map('strtoupper', $activeCodes);
        } else {
            $this->configuredActiveCurrencyCodes = [
                'AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CNY', 'CZK', 'DKK', 'EUR',  'GBP', 'HKD',
                'HUF', 'IDR', 'ILS', 'INR', 'ISK', 'JPY', 'KRW', 'MXN', 'MYR', 'NOK',
                'NZD', 'PHP', 'PLN', 'RON', 'SEK', 'SGD', 'THB', 'TRY', 'USD', 'ZAR',
            ];
        }

    }

    /**
     * Get the configured base currency model.
     */
    public function baseCurrency(): Currency
    {
        if ($this->baseCurrency instanceof Currency) {
            return $this->baseCurrency;
        }

        $configuredBaseCurrencyCode = Config::string('meridian.base_currency_code', 'USD');

        /** @var Currency $baseCurrency */
        $baseCurrency = $this->findByCode($configuredBaseCurrencyCode, false);

        $this->baseCurrency = $baseCurrency;

        return $baseCurrency;
    }

    /**
     * Gets the current display currency model from session.
     * Falls back to base currency if not set, invalid, or no exchange rate exists from base.
     */
    public function get(): Currency
    {
        if ($this->currency instanceof Currency) {
            return $this->currency;
        }

        $currency = null;

        $baseCurrency = $this->baseCurrency();
        $displayCurrencyCode = Session::get(self::SESSION_CURRENCY_CODE);

        if (! empty($displayCurrencyCode) && is_string($displayCurrencyCode)) {
            $currency = $this->findByCode($displayCurrencyCode);
            if (! $currency instanceof Currency || ! $currency->enabled) {
                $currency = null;
                Log::warning('Display currency from session not found or disabled. Falling back to base.', ['session_code' => $displayCurrencyCode]);
            }
        }

        $this->currency = $currency ?? $baseCurrency;

        return $this->currency;
    }

    /**
     * Sets the display currency in the session.
     *
     * @param  string  $currencyCode  The ISO 4217 alpha-3 currency code.
     */
    public function set(string $currencyCode): void
    {
        $currency = false;
        $currencyCode = mb_strtoupper($currencyCode);

        if (in_array($currencyCode, $this->configuredActiveCurrencyCodes)) {
            $currency = $this->findByCode($currencyCode);
        }

        if (! $currency || ! $currency->enabled) {
            $currency = $this->baseCurrency();
        }

        $this->currency = $currency;
        Session::put(self::SESSION_CURRENCY_CODE, $currency->code);
    }

    /**
     * Get the list of configured "active" currency models.
     * If `meridian.active_currencies` is not set or empty, it returns all enabled currencies.
     *
     * @return Collection<int, Currency>
     */
    public function list(): Collection
    {
        $cacheKey = 'meridian.active_currencies_collection';
        $cacheTtlMinutes = Config::integer('meridian.cache_duration_minutes.active_currencies_list', 60);

        /** @var Collection<int, Currency> */
        return Cache::remember($cacheKey, now()->addMinutes($cacheTtlMinutes), fn () => Currency::query()->whereIn('code', $this->configuredActiveCurrencyCodes)
            ->where('enabled', true)
            ->orderBy('code')
            ->get());
    }

    /**
     * Get all currencies, optionally from cache.
     *
     * @param  bool  $useCache  Whether to use cache.
     * @param  int  $cacheTtlMinutes  Cache TTL in minutes.
     * @return Collection<int, Currency>
     */
    public function all(bool $useCache = true, int $cacheTtlMinutes = 60): Collection
    {
        if (! $useCache) {
            /** @var Collection<int, Currency> */
            return Currency::query()->orderBy('name')->get();
        }

        /** @var Collection<int, Currency> */
        return Cache::remember(
            'currencies.all',
            now()->addMinutes($cacheTtlMinutes),
            fn () => Currency::query()->orderBy('name')->get()
        );
    }

    /**
     * Find a currency by its ID.
     *
     * @param  int  $id  The currency ID.
     * @param  bool  $useCache  Whether to use cache.
     * @param  int  $cacheTtlMinutes  Cache TTL in minutes.
     */
    public function findById(int $id, bool $useCache = true, int $cacheTtlMinutes = 60): ?Currency
    {
        if (! $useCache) {
            return Currency::query()->find($id);
        }

        /** @var Currency|null */
        return Cache::remember(
            'currency.id.'.$id,
            now()->addMinutes($cacheTtlMinutes),
            fn (): ?Currency => Currency::query()->find($id)
        );
    }

    /**
     * Find a currency by its ISO alpha code (e.g., 'USD', 'EUR').
     *
     * @param  string  $code  The ISO 4217 alpha-3 currency code (case-insensitive).
     * @param  bool  $useCache  Whether to use cache.
     * @param  int  $cacheTtlMinutes  Cache TTL in minutes.
     */
    public function findByCode(string $code, bool $useCache = true, int $cacheTtlMinutes = 60): ?Currency
    {
        $code = mb_strtoupper($code);

        if (! $useCache) {
            return Currency::query()->where('code', $code)->first();
        }

        /** @var Currency|null */
        return Cache::remember(
            'currency.code.'.$code,
            now()->addMinutes($cacheTtlMinutes),
            fn (): ?Currency => Currency::query()->where('code', $code)->first()
        );
    }
}
