<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services;

use Denprog\Meridian\Models\Country;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CountryService
{
    /**
     * Get all countries, optionally from cache.
     *
     * @param  bool  $useCache  Whether to use cache.
     * @param  int  $cacheTtlMinutes  Cache TTL in minutes.
     * @return Collection<int, Country>
     */
    public function getAllCountries(bool $useCache = true, int $cacheTtlMinutes = 60): Collection
    {
        if ($useCache) {
            /** @var Collection<int, Country> */
            return Cache::remember('countries.all', now()->addMinutes($cacheTtlMinutes), fn () => Country::query()->orderBy('name')->get());
        }

        /** @var Collection<int, Country> */
        return Country::query()->orderBy('name')->get();
    }

    /**
     * Find a country by its ISO Alpha-2 code.
     *
     * @param  string  $isoAlpha2  The ISO Alpha-2 code.
     * @param  bool  $useCache  Whether to use cache.
     * @param  int  $cacheTtlMinutes  Cache TTL in minutes.
     */
    public function findCountryByIsoAlpha2(string $isoAlpha2, bool $useCache = true, int $cacheTtlMinutes = 60): ?Country
    {
        $cacheKey = 'country.iso_alpha_2.'.mb_strtoupper($isoAlpha2);
        if ($useCache) {
            /** @var Country|null */
            return Cache::remember($cacheKey, now()->addMinutes($cacheTtlMinutes), fn () => Country::query()->where('iso_alpha_2', mb_strtoupper($isoAlpha2))->first());
        }

        return Country::query()->where('iso_alpha_2', mb_strtoupper($isoAlpha2))->first();
    }

    /**
     * Find a country by its ID.
     *
     * @param  int  $id  The country ID.
     * @param  bool  $useCache  Whether to use cache.
     * @param  int  $cacheTtlMinutes  Cache TTL in minutes.
     */
    public function findCountryById(int $id, bool $useCache = true, int $cacheTtlMinutes = 60): ?Country
    {
        $cacheKey = 'country.id.'.$id;
        if ($useCache) {
            /** @var Country|null */
            return Cache::remember($cacheKey, now()->addMinutes($cacheTtlMinutes), fn () => Country::query()->find($id));
        }

        return Country::query()->find($id);
    }
}
