<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services;

use Denprog\Meridian\Contracts\CountryServiceContract;
use Denprog\Meridian\Enums\Continent;
use Denprog\Meridian\Models\Country;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RuntimeException;

final class CountryService implements CountryServiceContract
{
    public const string SESSION_KEY_USER_COUNTRY = CountryServiceContract::SESSION_KEY_USER_COUNTRY;

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
     * Get the user's selected country's continent.
     */
    public function continent(): Continent
    {
        return $this->get()->continent_code;
    }

    /**
     * Get the user's selected country's name.
     */
    public function name(): string
    {
        return $this->get()->name;
    }

    /**
     * Get the user's selected country's official name.
     */
    public function officialName(): ?string
    {
        return $this->get()->official_name;
    }

    /**
     * Get the user's selected country's native name.
     */
    public function nativeName(): ?string
    {
        return $this->get()->native_name;
    }

    /**
     * Get the user's selected country's ISO 3166-1 alpha-2 code.
     */
    public function code(): string
    {
        return $this->get()->iso_alpha_2;
    }

    /**
     * Get the user's selected country's ISO 3166-1 alpha-2 code.
     */
    public function isoAlpha2Code(): string
    {
        return $this->get()->iso_alpha_2;
    }

    /**
     * Get the user's selected country's ISO 3166-1 alpha-3 code.
     */
    public function isoAlpha3Code(): string
    {
        return $this->get()->iso_alpha_3;
    }

    /**
     * Get the user's selected country's ISO 3166-1 numeric code.
     */
    public function numericCode(): ?string
    {
        return $this->get()->iso_numeric;
    }

    /**
     * Get the user's selected country's phone calling code.
     */
    public function phoneCode(): ?string
    {
        return $this->get()->phone_code;
    }

    /**
     * Get the user's selected country's currency code.
     */
    public function currencyCode(): ?string
    {
        return $this->get()->currency_code;
    }

    /**
     * Get the default country's continent.
     */
    public function defaultContinent(): Continent
    {
        return $this->default()->continent_code;
    }

    /**
     * Get the default country's name.
     */
    public function defaultName(): string
    {
        return $this->default()->name;
    }

    /**
     * Get the default country's official name.
     */
    public function defaultOfficialName(): ?string
    {
        return $this->default()->official_name;
    }

    /**
     * Get the default country's native name.
     */
    public function defaultNativeName(): ?string
    {
        return $this->default()->native_name;
    }

    /**
     * Get the default country's ISO 3166-1 alpha-2 code.
     */
    public function defaultCode(): string
    {
        return $this->default()->iso_alpha_2;
    }

    /**
     * Get the default country's ISO 3166-1 alpha-2 code.
     */
    public function defaultIsoAlpha2Code(): string
    {
        return $this->default()->iso_alpha_2;
    }

    /**
     * Get the default country's ISO 3166-1 alpha-3 code.
     */
    public function defaultIsoAlpha3Code(): string
    {
        return $this->default()->iso_alpha_3;
    }

    /**
     * Get the default country's ISO 3166-1 numeric code.
     */
    public function defaultNumericCode(): ?string
    {
        return $this->default()->iso_numeric;
    }

    /**
     * Get the default country's phone calling code.
     */
    public function defaultPhoneCode(): ?string
    {
        return $this->default()->phone_code;
    }

    /**
     * Get the default country's currency code.
     */
    public function defaultCurrencyCode(): ?string
    {
        return $this->default()->currency_code;
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

        $defaultIsoCodeSetting = Config::string('meridian.default_country_iso_code', 'US');
        $country = $this->findByIsoAlpha2Code($defaultIsoCodeSetting);

        if (! $country instanceof Country && $defaultIsoCodeSetting !== 'US') {
            $country = $this->findByIsoAlpha2Code('US');
        }

        if (! $country instanceof Country) {
            throw new RuntimeException("Default country ('$defaultIsoCodeSetting' or ultimate fallback 'US') could not be found. Please ensure a valid default country exists in the database.");
        }

        $this->defaultCountry = $country;

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
