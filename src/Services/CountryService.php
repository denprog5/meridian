<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services;

use Denprog\Meridian\Enums\Continent;
use Denprog\Meridian\Models\Country;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CountryService
{
    public const string SESSION_KEY_USER_COUNTRY = 'meridian.user_country_iso_alpha_2';

    private ?Country $defaultCountry = null;

    private ?Country $country = null;

    /**
     * Get the user's selected country from the session.
     *
     * @return Country The Country model if set and valid, otherwise null.
     */
    public function get(): Country
    {
        if ($this->country instanceof Country) {
            return $this->country;
        }

        $countryIsoCode = Session::get(self::SESSION_KEY_USER_COUNTRY);

        if (empty($countryIsoCode) || ! is_string($countryIsoCode)) {
            $this->country = $this->default();

            return $this->country;
        }

        $country = $this->findByIsoAlpha2Code($countryIsoCode);

        if (! $country instanceof Country) {
            $country = $this->default();
        }

        $this->country = $country;

        return $country;
    }

    /**
     * Set the user's selected country in the session.
     *
     * @param  string  $countryIsoAlpha2Code  The ISO Alpha-2 code of the country.
     */
    public function set(string $countryIsoAlpha2Code): void
    {
        $countryIsoAlpha2Code = mb_strtoupper($countryIsoAlpha2Code);
        $country = $this->findByIsoAlpha2Code($countryIsoAlpha2Code, false);

        if (! $country instanceof Country) {
            Log::warning('Attempt to set user country to non-existent or disabled country.', ['code' => $countryIsoAlpha2Code]);

            return;
        }

        $this->country = $country;
        Session::put(self::SESSION_KEY_USER_COUNTRY, $country->iso_alpha_2);
    }

    /**
     * Get the default country from configuration.
     *
     * @return Country The default Country model if configured and valid, otherwise null.
     */
    public function default(): Country
    {
        if ($this->defaultCountry instanceof Country) {
            return $this->defaultCountry;
        }

        $this->defaultCountry = $this->findByIsoAlpha2Code(Config::string('meridian.default_country_iso_code', 'US'));

        return $this->defaultCountry;
    }

    /**
     * Get all countries, optionally from cache.
     *
     * @param  bool  $useCache  Whether to use cache.
     * @param  int  $cacheTtlMinutes  Cache TTL in minutes.
     * @return Collection<int, Country>
     */
    public function all(bool $useCache = true, int $cacheTtlMinutes = 60): Collection
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
    public function findByIsoAlpha3Code(string $isoAlpha3Code, bool $useCache = true, int $cacheTtlMinutes = 60): ?Country
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
    public function findByContinent(Continent $continent, bool $useCache = true, int $cacheTtlMinutes = 60): Collection
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
    public function findById(int $id, bool $useCache = true, int $cacheTtlMinutes = 60): ?Country
    {
        $cacheKey = 'country.id.'.$id;
        if ($useCache) {
            /** @var Country|null */
            return Cache::remember($cacheKey, now()->addMinutes($cacheTtlMinutes), fn () => Country::query()->find($id));
        }

        return Country::query()->find($id);
    }
}
