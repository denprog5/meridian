<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services;

use Denprog\Meridian\Enums\Continent;
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
     * @param  string  $isoAlpha2Code  The ISO Alpha-2 code.
     * @param  bool  $useCache  Whether to use cache.
     * @param  int  $cacheTtlMinutes  Cache TTL in minutes.
     */
    public function findByIsoAlpha2Code(string $isoAlpha2Code, bool $useCache = true, int $cacheTtlMinutes = 60): ?Country
    {
        $cacheKey = 'country.iso_alpha_2.'.mb_strtoupper($isoAlpha2Code);
        if ($useCache) {
            /** @var Country|null */
            return Cache::remember($cacheKey, now()->addMinutes($cacheTtlMinutes), fn () => Country::query()->where('iso_alpha_2', mb_strtoupper($isoAlpha2Code))->first());
        }

        return Country::query()->where('iso_alpha_2', mb_strtoupper($isoAlpha2Code))->first();
    }

    /**
     * Find a country by its ISO Alpha-3 code.
     *
     * @param  string  $isoAlpha3Code  The ISO Alpha-3 code.
     * @param  bool  $useCache  Whether to use cache.
     * @param  int  $cacheTtlMinutes  Cache TTL in minutes.
     */
    public function findCountryByIsoAlpha3Code(string $isoAlpha3Code, bool $useCache = true, int $cacheTtlMinutes = 60): ?Country
    {
        $cacheKey = 'country.iso_alpha_3.'.mb_strtoupper($isoAlpha3Code);
        if ($useCache) {
            /** @var Country|null */
            return Cache::remember($cacheKey, now()->addMinutes($cacheTtlMinutes), fn () => Country::query()->where('iso_alpha_3', mb_strtoupper($isoAlpha3Code))->first());
        }

        return Country::query()->where('iso_alpha_3', mb_strtoupper($isoAlpha3Code))->first();
    }

    /**
     * Get countries by a specific continent.
     *
     * @param  Continent  $continent  The continent enum instance.
     * @param  bool  $useCache  Whether to use cache.
     * @param  int  $cacheTtlMinutes  Cache TTL in minutes.
     * @return Collection<int, Country>
     */
    public function getCountriesByContinent(Continent $continent, bool $useCache = true, int $cacheTtlMinutes = 60): Collection
    {
        $cacheKey = 'countries.continent.'.$continent->value;
        if ($useCache) {
            /** @var Collection<int, Country> */
            return Cache::remember($cacheKey, now()->addMinutes($cacheTtlMinutes), fn () => Country::query()->where('continent_code', $continent->value)->orderBy('name')->get());
        }

        /** @var Collection<int, Country> */
        return Country::query()->where('continent_code', $continent->value)->orderBy('name')->get();
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
