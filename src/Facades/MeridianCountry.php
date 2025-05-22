<?php

declare(strict_types=1);

namespace Denprog\Meridian\Facades;

use Denprog\Meridian\Enums\Continent;
use Denprog\Meridian\Models\Country;
use Denprog\Meridian\Services\CountryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Country get()
 * @method static void set(string $countryIsoAlpha2Code)
 * @method static Country default()
 * @method static Collection<int, Country> all(bool $useCache = true, int $cacheTtlMinutes = 60)
 * @method static Country|null findByIsoAlpha2Code(string $isoAlpha2Code, bool $useCache = true, int $cacheTtlMinutes = 60)
 * @method static Country|null findByIsoAlpha3Code(string $isoAlpha3Code, bool $useCache = true, int $cacheTtlMinutes = 60)
 * @method static Collection<int, Country> findByContinent(Continent $continent, bool $useCache = true, int $cacheTtlMinutes = 60)
 * @method static Country|null findById(string $name, bool $useCache = true, int $cacheTtlMinutes = 60)
 *
 * @see CountryService
 */
class MeridianCountry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CountryService::class;
    }
}
