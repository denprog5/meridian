<?php

declare(strict_types=1);

namespace Denprog\Meridian\Contracts;

use Denprog\Meridian\Enums\Continent;
use Denprog\Meridian\Models\Country;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

interface CountryServiceContract
{
    public const SESSION_KEY_USER_COUNTRY = 'meridian.user_country_iso_alpha_2';

    /**
     * Get the user's selected country from the session.
     * If no country is set in the session, or if the set country is invalid,
     * it falls back to the default country.
     *
     * @return Country The active Country model.
     */
    public function get(): Country;

    /**
     * Set the user's selected country in the session.
     * The country code is validated against existing and enabled countries.
     * If the country code is invalid, a warning is logged, and the session is not updated.
     *
     * @param  string  $countryIsoAlpha2Code  The ISO 3166-1 Alpha-2 code of the country.
     */
    public function set(string $countryIsoAlpha2Code): void;

    /**
     * Get the default country.
     * The default country is determined by the 'meridian.default_country_iso_code' config value.
     * If the configured default is not found, it falls back to 'US'.
     * Throws a RuntimeException if neither the configured default nor 'US' can be found.
     *
     * @return Country The default Country model.
     *
     * @throws RuntimeException If no valid default country can be resolved.
     */
    public function default(): Country;

    /**
     * Get all countries.
     * Results can be optionally retrieved from cache.
     *
     * @param  bool  $useCache  Whether to use cache. Defaults to true.
     * @param  int  $cacheTtlMinutes  Cache Time-To-Live in minutes. Defaults to 60.
     * @return Collection<int, Country> A collection of Country models.
     */
    public function all(bool $useCache = true, int $cacheTtlMinutes = 60): Collection;

    /**
     * Find a country by its ISO 3166-1 Alpha-2 code.
     * Results can be optionally retrieved from cache.
     *
     * @param  string  $isoAlpha2Code  The ISO 3166-1 Alpha-2 code.
     * @param  bool  $useCache  Whether to use cache. Defaults to true.
     * @param  int  $cacheTtlMinutes  Cache Time-To-Live in minutes. Defaults to 60.
     * @return Country|null The Country model if found, otherwise null.
     */
    public function findByIsoAlpha2Code(string $isoAlpha2Code, bool $useCache = true, int $cacheTtlMinutes = 60): ?Country;

    /**
     * Find a country by its ISO 3166-1 Alpha-3 code.
     * Results can be optionally retrieved from cache.
     *
     * @param  string  $isoAlpha3Code  The ISO 3166-1 Alpha-3 code.
     * @param  bool  $useCache  Whether to use cache. Defaults to true.
     * @param  int  $cacheTtlMinutes  Cache Time-To-Live in minutes. Defaults to 60.
     * @return Country|null The Country model if found, otherwise null.
     */
    public function findByIsoAlpha3Code(string $isoAlpha3Code, bool $useCache = true, int $cacheTtlMinutes = 60): ?Country;

    /**
     * Get countries by a specific continent.
     * Results can be optionally retrieved from cache.
     *
     * @param  Continent  $continent  The continent enum instance.
     * @param  bool  $useCache  Whether to use cache. Defaults to true.
     * @param  int  $cacheTtlMinutes  Cache Time-To-Live in minutes. Defaults to 60.
     * @return Collection<int, Country> A collection of Country models.
     */
    public function findByContinent(Continent $continent, bool $useCache = true, int $cacheTtlMinutes = 60): Collection;

    /**
     * Find a country by its ID.
     * Results can be optionally retrieved from cache.
     *
     * @param  int  $id  The country ID.
     * @param  bool  $useCache  Whether to use cache. Defaults to true.
     * @param  int  $cacheTtlMinutes  Cache Time-To-Live in minutes. Defaults to 60.
     * @return Country|null The Country model if found, otherwise null.
     */
    public function findById(int $id, bool $useCache = true, int $cacheTtlMinutes = 60): ?Country;
}
