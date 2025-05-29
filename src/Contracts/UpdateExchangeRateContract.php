<?php

declare(strict_types=1);

namespace Denprog\Meridian\Contracts;

use Illuminate\Support\Carbon;

/**
 * Interface UpdateExchangeRateContract.
 *
 * Defines the contract for services that update exchange rates.
 */
interface UpdateExchangeRateContract
{
    /**
     * Updates exchange rates.
     *
     * Fetches exchange rates from a provider and stores them.
     * If no parameters are provided, it should attempt to update rates
     * for all configured target currencies against the system's base currency for the current date.
     *
     * @param  string|null  $baseCurrencyCode  The ISO 4217 code of the base currency. Defaults to system base currency.
     * @param  array<int, string>|null  $targetCurrencyCodes  An array of ISO 4217 codes for target currencies. Defaults to all configured target currencies.
     * @param  Carbon|null  $date  The date for which to fetch rates. Defaults to the current date.
     * @return bool True on success, false on failure or if no rates were updated.
     */
    public function updateRates(
        ?string $baseCurrencyCode = null,
        ?array $targetCurrencyCodes = null,
        ?Carbon $date = null
    ): bool;
}
