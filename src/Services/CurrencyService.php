<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services;

use Denprog\Meridian\Models\Currency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Service class for handling currency-related operations.
 *
 * This service provides methods to retrieve and manage currency data,
 * with built-in caching support for improved performance.
 */
class CurrencyService
{
    /**
     * Get all currencies, optionally from cache.
     *
     * @param  bool  $useCache  Whether to use cache.
     * @param  int  $cacheTtlMinutes  Cache TTL in minutes.
     * @return Collection<int, Currency>
     */
    public function getAllCurrencies(bool $useCache = true, int $cacheTtlMinutes = 60): Collection
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
    public function findCurrencyById(int $id, bool $useCache = true, int $cacheTtlMinutes = 60): ?Currency
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
     * @param  string  $isoAlphaCode  The ISO 4217 alpha-3 currency code (case-insensitive).
     * @param  bool  $useCache  Whether to use cache.
     * @param  int  $cacheTtlMinutes  Cache TTL in minutes.
     */
    public function findCurrencyByIsoAlphaCode(string $isoAlphaCode, bool $useCache = true, int $cacheTtlMinutes = 60): ?Currency
    {
        $isoAlphaCode = mb_strtoupper($isoAlphaCode);

        if (! $useCache) {
            return Currency::query()->where('iso_alpha_code', $isoAlphaCode)->first();
        }

        /** @var Currency|null */
        return Cache::remember(
            'currency.iso_alpha_code.'.$isoAlphaCode,
            now()->addMinutes($cacheTtlMinutes),
            fn (): ?Currency => Currency::query()->where('iso_alpha_code', $isoAlphaCode)->first()
        );
    }

    /**
     * Find a currency by its ISO numeric code (e.g., 840 for USD, 978 for EUR).
     *
     * @param  string  $isoNumericCode  The ISO 4217 numeric currency code.
     * @param  bool  $useCache  Whether to use cache.
     * @param  int  $cacheTtlMinutes  Cache TTL in minutes.
     */
    public function findCurrencyByIsoNumericCode(string $isoNumericCode, bool $useCache = true, int $cacheTtlMinutes = 60): ?Currency
    {
        if (! $useCache) {
            return Currency::query()->where('iso_numeric_code', $isoNumericCode)->first();
        }

        /** @var Currency|null */
        return Cache::remember(
            'currency.iso_numeric_code.'.$isoNumericCode,
            now()->addMinutes($cacheTtlMinutes),
            static fn (): ?Currency => Currency::query()->where('iso_numeric_code', $isoNumericCode)->first()
        );
    }
}
