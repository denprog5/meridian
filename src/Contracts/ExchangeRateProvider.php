<?php

declare(strict_types=1);

namespace Denprog\Meridian\Contracts;

use Illuminate\Support\Carbon;

interface ExchangeRateProvider
{
    /**
     * Fetches exchange rates.
     *
     * @param  string  $baseCurrencyCode  The base currency code (e.g., 'USD').
     * @param  array<string>|null  $targetCurrencyCodes  Array of target currency codes. If null, fetch all available.
     * @param  Carbon|null  $date  The date for which to fetch rates. If null, fetch latest available.
     * @return array<string, float>|null An associative array of currency codes to their rates against the base currency, or null on failure.
     */
    public function getRates(string $baseCurrencyCode, ?array $targetCurrencyCodes = null, ?Carbon $date = null): ?array;
}
