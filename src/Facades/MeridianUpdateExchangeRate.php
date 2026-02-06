<?php

declare(strict_types=1);

namespace Denprog\Meridian\Facades;

use Denprog\Meridian\Contracts\UpdateExchangeRateContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for updating exchange rates from configured providers.
 *
 * @method static bool updateRates(?string $baseCurrencyCode = null, ?array<int, string> $targetCurrencyCodes = null, ?Carbon $date = null) Update exchange rates from the configured provider.
 *
 * @see UpdateExchangeRateContract
 */
class MeridianUpdateExchangeRate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UpdateExchangeRateContract::class;
    }
}
