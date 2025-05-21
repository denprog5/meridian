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
