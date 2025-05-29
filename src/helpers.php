<?php

declare(strict_types=1);

use Denprog\Meridian\Contracts\CountryServiceContract;
use Denprog\Meridian\Contracts\CurrencyConverterContract;
use Denprog\Meridian\Contracts\CurrencyServiceContract;
use Denprog\Meridian\Contracts\LanguageServiceContract;
use Denprog\Meridian\Models\Country;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Models\Language;

if (! function_exists('currency')) {
    /**
     * Get the CurrencyService instance or a specific currency if code is provided.
     *
     * @param  string|null  $currencyCode  Optional currency code to retrieve a specific currency.
     *                                     If null, returns the CurrencyService instance.
     */
    function currency(?string $currencyCode = null): CurrencyServiceContract|Currency|null
    {
        /** @var CurrencyServiceContract $service */
        $service = app(CurrencyServiceContract::class);

        if ($currencyCode !== null) {
            return $service->findByCode($currencyCode);
        }

        return $service;
    }
}

if (! function_exists('country')) {
    /**
     * Get the CountryService instance or a specific country if ISO Alpha-2 code is provided.
     *
     * @param  string|null  $countryIsoAlpha2Code  Optional ISO Alpha-2 code to retrieve a specific country.
     *                                             If null, returns the CountryService instance.
     */
    function country(?string $countryIsoAlpha2Code = null): Country|CountryServiceContract|null
    {
        /** @var CountryServiceContract $service */
        $service = app(CountryServiceContract::class);

        if ($countryIsoAlpha2Code !== null) {
            return $service->findByIsoAlpha2Code($countryIsoAlpha2Code);
        }

        return $service;
    }
}

if (! function_exists('exchangeRate')) {
    /**
     * Get the CurrencyConverterContract instance.
     */
    function exchangeRate(float|int $amount, bool $returnFormatted = false): CurrencyConverterContract|string|float
    {
        /** @var CurrencyConverterContract $service */
        $service = app(CurrencyConverterContract::class);

        if (! empty($amount)) {
            return $service->convert($amount, $returnFormatted);
        }

        return $service;
    }
}

if (! function_exists('language')) {
    /**
     * Get the current active Language model instance.
     */
    function language(?string $languageCode = null): Language|LanguageServiceContract|null
    {
        /** @var LanguageServiceContract $service */
        $service = app(LanguageServiceContract::class);

        if ($languageCode !== null) {
            return $service->findByCode($languageCode);
        }

        return $service;
    }
}
