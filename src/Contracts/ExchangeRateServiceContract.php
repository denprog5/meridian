<?php

declare(strict_types=1);

namespace Denprog\Meridian\Contracts;

use Illuminate\Support\Carbon;

interface ExchangeRateServiceContract
{
    /**
     * Fetches and stores exchange rates from the configured provider.
     *
     * @param  string|null  $baseCurrency  The base currency code (e.g., 'USD'). Defaults to config.
     * @param  array<string>|null  $targetCurrencies  Array of target currency codes. Defaults to all available from provider for the base.
     * @param  string|Carbon|null  $date  Date for rates ('latest' or YYYY-MM-DD). Defaults to 'latest'.
     * @return array<string, float>|null An array of currency codes to rates, or null on failure.
     */
    public function fetchAndStoreRatesFromProvider(
        ?string $baseCurrency = null,
        ?array $targetCurrencies = null,
        string|Carbon|null $date = null
    ): ?array;

    /**
     * Converts an amount from one currency to another.
     *
     * @param  float  $amount  The amount to convert.
     * @param  string  $fromCurrencySymbolOrCode  The currency code or symbol to convert from.
     * @param  string  $toCurrencySymbolOrCode  The currency code or symbol to convert to.
     * @param  string|Carbon|null  $date  The date for the exchange rate. Defaults to latest.
     * @return float|null The converted amount, or null if conversion is not possible.
     */
    public function convert(
        float $amount,
        string $fromCurrencySymbolOrCode,
        string $toCurrencySymbolOrCode,
        string|Carbon|null $date = null
    ): ?float;

    /**
     * Retrieves available target currency codes for a given base currency and date.
     *
     * @param  string  $baseCurrencyCode  The base currency code.
     * @param  Carbon|null  $date  The specific date for rates, or null for the latest rates.
     * @return array<int, string> An array of target currency codes.
     */
    public function getAvailableTargetCurrencies(string $baseCurrencyCode, ?Carbon $date = null): array;
}
