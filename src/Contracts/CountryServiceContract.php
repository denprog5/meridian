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
     * Get the user's selected country's continent.
     *
     * @return Continent The continent enum instance.
     */
    public function continent(): Continent;

    /**
     * Get the user's selected country's name.
     *
     * @return string The country name.
     */
    public function name(): string;

    /**
     * Get the user's selected country's official name.
     *
     * @return string|null The country official name, or null if not available.
     */
    public function officialName(): ?string;

    /**
     * Get the user's selected country's native name.
     *
     * @return string|null The country native name, or null if not available.
     */
    public function nativeName(): ?string;

    /**
     * Get the user's selected country's ISO 3166-1 alpha-2 code.
     *
     * @return string The country ISO 3166-1 alpha-2 code.
     */
    public function code(): string;

    /**
     * Get the user's selected country's ISO 3166-1 alpha-2 code.
     *
     * @return string The country ISO 3166-1 alpha-2 code.
     */
    public function isoAlpha2Code(): string;

    /**
     * Get the user's selected country's ISO 3166-1 alpha-3 code.
     *
     * @return string The country ISO 3166-1 alpha-3 code.
     */
    public function isoAlpha3Code(): string;

    /**
     * Get the user's selected country's ISO 3166-1 numeric code.
     *
     * @return string|null The country ISO 3166-1 numeric code, or null if not available.
     */
    public function numericCode(): ?string;

    /**
     * Get the user's selected country's phone calling code.
     *
     * @return string|null The country phone calling code, or null if not available.
     */
    public function phoneCode(): ?string;

    /**
     * Get the user's selected country's currency code.
     *
     * @return string|null The country currency code, or null if not available.
     */
    public function currencyCode(): ?string;

    /**
     * Get the default country's continent.
     *
     * @return Continent The continent enum instance.
     */
    public function defaultContinent(): Continent;

    /**
     * Get the default country's name.
     *
     * @return string The country name.
     */
    public function defaultName(): string;

    /**
     * Get the default country's official name.
     *
     * @return string|null The country official name, or null if not available.
     */
    public function defaultOfficialName(): ?string;

    /**
     * Get the default country's native name.
     *
     * @return string|null The country native name, or null if not available.
     */
    public function defaultNativeName(): ?string;

    /**
     * Get the default country's ISO 3166-1 alpha-2 code.
     *
     * @return string The country ISO 3166-1 alpha-2 code.
     */
    public function defaultCode(): string;

    /**
     * Get the default country's ISO 3166-1 alpha-2 code.
     *
     * @return string The country ISO 3166-1 alpha-2 code.
     */
    public function defaultIsoAlpha2Code(): string;

    /**
     * Get the default country's ISO 3166-1 alpha-3 code.
     *
     * @return string The country ISO 3166-1 alpha-3 code.
     */
    public function defaultIsoAlpha3Code(): string;

    /**
     * Get the default country's ISO 3166-1 numeric code.
     *
     * @return string|null The country ISO 3166-1 numeric code, or null if not available.
     */
    public function defaultNumericCode(): ?string;

    /**
     * Get the default country's phone calling code.
     *
     * @return string|null The country phone calling code, or null if not available.
     */
    public function defaultPhoneCode(): ?string;

    /**
     * Get the default country's currency code.
     *
     * @return string|null The country currency code, or null if not available.
     */
    public function defaultCurrencyCode(): ?string;

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
