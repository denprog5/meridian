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
     * @param float $amount The amount to convert (in system base currency).
     * @param bool $returnFormatted If true, returns the amount formatted as a string with the active display currency symbol and locale rules.
     *                              If false, returns the raw converted float amount.
     * @param string|null $locale The locale to use for formatting if $returnFormatted is true. Defaults to the application's current locale.
     * @return string|float|null The converted amount (string if formatted, float otherwise), or null if conversion is not possible.
     */
    public function convert(float $amount, bool $returnFormatted = false, ?string $locale = null): mixed;

    /**
     * Converts an amount between two specified currencies, optionally for a specific date.
     *
     * @param float $amount The amount to convert.
     * @param string $toCurrencyCode The ISO 4217 code of the currency to convert to.
     * @param string|null $fromCurrencyCode The ISO 4217 code of the currency to convert from. Defaults to null (system base currency).
     * @param bool $returnFormatted If true, returns the amount formatted as a string with the target currency symbol and locale rules.
     *                              If false, returns the raw converted float amount.
     * @param Carbon|null $date The date for which to fetch the exchange rate. Defaults to null (latest available rate).
     * @param string|null $locale The locale to use for formatting if $returnFormatted is true. Defaults to the application's current locale.
     * @return string|float|null The converted amount (string if formatted, float otherwise), or null if conversion is not possible.
     */
    public function convertBetween(float $amount, string $toCurrencyCode, ?string $fromCurrencyCode = null, bool $returnFormatted = false, ?Carbon $date = null, ?string $locale = null): mixed;

    /**
     * Formats a given amount using specified currency and locale rules.
     *
     * @param float $amount The amount to format.
     * @param string $currencyCode The ISO 4217 code of the currency.
     * @param string|null $locale The locale for formatting (e.g., 'en_US', 'de_DE'). Defaults to null (application's current locale).
     * @return string|null The formatted currency string, or null if formatting fails.
     */
    public function format(float $amount, string $currencyCode, ?string $locale = null): ?string;

    /**
     * Retrieves the exchange rate between a base currency and a target currency for a specific date.
     *
     * @param string $targetCurrencyCode The ISO 4217 code of the target currency.
     * @param string|null $baseCurrencyCode The ISO 4217 code of the base currency. Defaults to null (system base currency).
     * @param Carbon|null $date The date for which to fetch the exchange rate. Defaults to null (latest available rate).
     * @return float|null The exchange rate, or null if the rate is not available.
     */
    public function getRate(string $targetCurrencyCode, ?string $baseCurrencyCode = null, ?Carbon $date = null): ?float;
}
