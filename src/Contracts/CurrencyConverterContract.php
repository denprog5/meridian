<?php

declare(strict_types=1);

namespace Denprog\Meridian\Contracts;

use Illuminate\Support\Carbon;

/**
 * Interface CurrencyConverterContract.
 *
 * Defines the contract for currency conversion, formatting, and rate retrieval services.
 */
interface CurrencyConverterContract
{
    /**
     * Converts a given amount from the system's base currency to the user's active display currency.
     *
     * The active display currency is determined by CurrencyService (e.g., from session or default).
     * Uses the latest available exchange rate.
     *
     * @param  float  $amount  The amount to convert (in system base currency).
     * @param  bool  $returnFormatted  If true, returns the amount formatted as a string with the active display currency symbol and locale rules.
     *                                 If false, returns the raw converted float amount.
     * @param  string|null  $locale  The locale to use for formatting if $returnFormatted is true. Defaults to the application's current locale.
     * @return string|float The converted amount (string if formatted, float otherwise).
     */
    public function convert(float $amount, bool $returnFormatted = false, ?string $locale = null): float|string;

    /**
     * Converts an amount between two specified currencies, optionally for a specific date.
     *
     * @param  float  $amount  The amount to convert.
     * @param  string  $toCurrencyCode  The ISO 4217 code of the currency to convert to.
     * @param  string|null  $fromCurrencyCode  The ISO 4217 code of the currency to convert from. Defaults to null (system base currency).
     * @param  bool  $returnFormatted  If true, returns the amount formatted as a string with the target currency symbol and locale rules.
     *                                 If false, returns the raw converted float amount.
     * @param  string|Carbon|null  $date  The date for which to fetch the exchange rate. Defaults to null (latest available rate).
     * @param  string|null  $locale  The locale to use for formatting if $returnFormatted is true. Defaults to the application's current locale.
     * @return string|float The converted amount (string if formatted, float otherwise).
     */
    public function convertBetween(float $amount, string $toCurrencyCode, ?string $fromCurrencyCode = null, bool $returnFormatted = false, string|Carbon|null $date = null, ?string $locale = null): float|string;

    /**
     * Formats a given amount using specified currency and locale rules.
     *
     * @param  float  $amount  The amount to format.
     * @param  string  $currencyCode  The ISO 4217 code of the currency.
     * @param  string|null  $locale  The locale for formatting (e.g., 'en_US', 'de_DE'). Defaults to null (application's current locale).
     * @return string The formatted currency string.
     */
    public function format(float $amount, string $currencyCode, ?string $locale = null): string;

    /**
     * Retrieves the exchange rate between a base currency and a target currency for a specific date.
     *
     * @param  string  $targetCurrencyCode  The ISO 4217 code of the target currency.
     * @param  string|null  $baseCurrencyCode  The ISO 4217 code of the base currency. Defaults to null (system base currency).
     * @param  string|Carbon|null  $date  The date for which to fetch the exchange rate. Defaults to null (latest available rate).
     * @return float The exchange rate.
     */
    public function getRate(string $targetCurrencyCode, ?string $baseCurrencyCode = null, string|Carbon|null $date = null): float;

    /**
     * Retrieves multiple exchange rates for a target currency against a base currency for a specific date.
     *
     * @param  string  $targetCurrencyCode  The ISO 4217 code of the target currency.
     * @param  string|null  $baseCurrencyCode  The ISO 4217 code of the base currency. Defaults to null (system base currency).
     * @param  string|Carbon|null  $date  The date for which to fetch the exchange rates. Defaults to null (latest available rates).
     * @return array<string, float>|null An associative array of currency codes to rates (e.g., ['EUR' => 0.93, 'GBP' => 0.81]), or null if rates are not available.
     */
    public function getRates(string $targetCurrencyCode, ?string $baseCurrencyCode = null, string|Carbon|null $date = null): ?array;
}
