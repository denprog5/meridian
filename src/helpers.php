<?php

declare(strict_types=1);

use Denprog\Meridian\Models\Country;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Models\Language;
use Denprog\Meridian\Services\CountryService;
use Denprog\Meridian\Services\CurrencyService;
use Denprog\Meridian\Services\ExchangeRateService;
use Denprog\Meridian\Services\LanguageService;

if (! function_exists('currency')) {
    /**
     * Get the CurrencyService instance or a specific currency if code is provided.
     *
     * @param  string|null  $currencyCode  Optional currency code to retrieve a specific currency.
     *                                     If null, returns the CurrencyService instance.
     */
    function currency(?string $currencyCode = null): CurrencyService|Currency|null
    {
        $service = app(CurrencyService::class);

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
    function country(?string $countryIsoAlpha2Code = null): Country|CountryService|null
    {
        $service = app(CountryService::class);

        if ($countryIsoAlpha2Code !== null) {
            return $service->findByIsoAlpha2Code($countryIsoAlpha2Code);
        }

        return $service;
    }
}

if (! function_exists('exchangeRate')) {
    /**
     * Get the ExchangeRateService instance.
     */
    function exchangeRate(): ExchangeRateService
    {
        /** @var ExchangeRateService */
        return app(ExchangeRateService::class);
    }
}

if (! function_exists('language')) {
    /**
     * Get the current active Language model instance.
     */
    function language(): Language
    {
        return app(LanguageService::class)->get();
    }
}
