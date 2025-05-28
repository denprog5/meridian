<?php

declare(strict_types=1);

namespace Denprog\Meridian\Contracts;

use Denprog\Meridian\Models\Currency;
use Illuminate\Support\Carbon;

interface ExchangeRateServiceContract
{
    /**
     * Converts an amount from the system's base currency to the active display currency.
     *
     * The system base currency is defined in `meridian.system_base_currency_code` config.
     * The active display currency is determined by the CurrencyService.
     *
     * @param  float  $amountInSystemBase  The amount in the system's base currency.
     * @param  bool  $withUnit  If true, returns a formatted string with currency symbol/code (e.g., "$123.45" or "123.45 USD").
     *                          If false (default), returns the raw float value.
     * @param  string|null  $locale  Optional locale for number formatting (e.g., 'en_US', 'de_DE').
     *                               Defaults to the application's current locale if null.
     * @return string|float|null The converted amount (string if $withUnit is true, float if false),
     *                           or null if conversion is not possible (e.g., rate unavailable, currency not found).
     */
    public function convert(float $amountInSystemBase, bool $withUnit = false, ?string $locale = null): string|float|null;

    /**
     * Converts an amount from a specified source currency to a specified target currency.
     *
     * This method is for ad-hoc conversions and does not use the system base or active display currency context directly,
     * unless those are passed as $fromCurrencyCode or $toCurrencyCode.
     *
     * @param  float  $amount  The amount to convert.
     * @param  string  $fromCurrencyCode  The ISO 4217 currency code to convert from (e.g., 'USD').
     * @param  string  $toCurrencyCode  The ISO 4217 currency code to convert to (e.g., 'EUR').
     * @param  bool  $withUnit  If true, returns a formatted string with currency symbol/code.
     *                          If false (default), returns the raw float value.
     * @param  string|null  $locale  Optional locale for number formatting.
     * @param  string|Carbon|null  $date  The specific date for the exchange rate ('latest', 'YYYY-MM-DD', or Carbon instance). Defaults to latest if null.
     * @return string|float|null The converted amount, or null if conversion is not possible.
     */
    public function convertBetween(float $amount, string $fromCurrencyCode, string $toCurrencyCode, bool $withUnit = false, ?string $locale = null, string|Carbon|null $date = null): string|float|null;

    /**
     * Gets the currently active display currency model.
     *
     * This is typically determined by the CurrencyService (e.g., from session or default).
     *
     * @return Currency The active display currency model.
     */
    public function getActiveDisplayCurrency(): Currency;

    /**
     * Gets the system's base currency model.
     *
     * This is defined in the `meridian.system_base_currency_code` configuration.
     *
     * @return Currency The system base currency model.
     */
    public function getSystemBaseCurrency(): Currency;

    /**
     * Fetches and stores exchange rates from the configured provider.
     *
     * This is typically used by an Artisan command to update rates periodically.
     *
     * @param  string|null  $baseCurrency  The base currency code (e.g., 'USD'). Defaults to provider's base or a configured pivot.
     * @param  array<string>|null  $targetCurrencies  Array of target currency codes. Defaults to all relevant/configured currencies.
     * @param  string|Carbon|null  $date  Date for rates ('latest' or YYYY-MM-DD). Defaults to 'latest'.
     * @return array<string, float>|null An array of currency codes to rates, or null on failure.
     */
    public function fetchAndStoreRatesFromProvider(
        ?string $baseCurrency = null,
        ?array $targetCurrencies = null,
        string|Carbon|null $date = null
    ): ?array;

    /**
     * Retrieves available target currency codes for a given base currency and date from the provider.
     *
     * @param  string  $baseCurrencyCode  The base currency code.
     * @param  Carbon|string|null  $date  The specific date for rates (Carbon, 'YYYY-MM-DD', or 'latest'), or null for the latest rates.
     * @return array<int, string> An array of target currency codes.
     */
    public function getAvailableTargetCurrencies(string $baseCurrencyCode, Carbon|string|null $date = null): array;
}
