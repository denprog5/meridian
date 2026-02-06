<?php

declare(strict_types=1);

namespace Denprog\Meridian\Facades;

use Denprog\Meridian\Contracts\CountryServiceContract;
use Denprog\Meridian\Enums\Continent;
use Denprog\Meridian\Models\Country;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for accessing country-related services.
 *
 * @method static Country get() Get the user's selected country from the session.
 * @method static void set(string $countryIsoAlpha2Code) Set the user's selected country in the session.
 * @method static Country default() Get the default country from configuration.
 * @method static Continent continent() Get the user's selected country's continent.
 * @method static string name() Get the user's selected country's name.
 * @method static string|null officialName() Get the user's selected country's official name.
 * @method static string|null nativeName() Get the user's selected country's native name.
 * @method static string code() Get the user's selected country's ISO 3166-1 alpha-2 code.
 * @method static string isoAlpha2Code() Get the user's selected country's ISO 3166-1 alpha-2 code.
 * @method static string isoAlpha3Code() Get the user's selected country's ISO 3166-1 alpha-3 code.
 * @method static string|null numericCode() Get the user's selected country's ISO 3166-1 numeric code.
 * @method static string|null phoneCode() Get the user's selected country's phone calling code.
 * @method static string|null currencyCode() Get the user's selected country's currency code.
 * @method static Continent defaultContinent() Get the default country's continent.
 * @method static string defaultName() Get the default country's name.
 * @method static string|null defaultOfficialName() Get the default country's official name.
 * @method static string|null defaultNativeName() Get the default country's native name.
 * @method static string defaultCode() Get the default country's ISO 3166-1 alpha-2 code.
 * @method static string defaultIsoAlpha2Code() Get the default country's ISO 3166-1 alpha-2 code.
 * @method static string defaultIsoAlpha3Code() Get the default country's ISO 3166-1 alpha-3 code.
 * @method static string|null defaultNumericCode() Get the default country's ISO 3166-1 numeric code.
 * @method static string|null defaultPhoneCode() Get the default country's phone calling code.
 * @method static string|null defaultCurrencyCode() Get the default country's currency code.
 * @method static Collection<int, Country> all(bool $useCache = true, int $cacheTtlMinutes = 60) Get all countries.
 * @method static Country|null findByCode(string $code, bool $useCache = true, int $cacheTtlMinutes = 60) Find a country by its ISO Alpha-2 code.
 * @method static Country|null findByIsoAlpha2Code(string $isoAlpha2Code, bool $useCache = true, int $cacheTtlMinutes = 60) Find a country by its ISO Alpha-2 code.
 * @method static Country|null findByIsoAlpha3Code(string $isoAlpha3Code, bool $useCache = true, int $cacheTtlMinutes = 60) Find a country by its ISO Alpha-3 code.
 * @method static Collection<int, Country> findByContinent(Continent $continent, bool $useCache = true, int $cacheTtlMinutes = 60) Get countries by a specific continent.
 * @method static Country|null findById(int $id, bool $useCache = true, int $cacheTtlMinutes = 60) Find a country by its ID.
 *
 * @see CountryServiceContract
 */
class MeridianCountry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CountryServiceContract::class;
    }
}
