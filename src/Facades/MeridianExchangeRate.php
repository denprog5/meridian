<?php

declare(strict_types=1);

namespace Denprog\Meridian\Facades;

use Denprog\Meridian\Services\ExchangeRateService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array<string, float>|null fetchAndStoreRatesFromProvider(string|null $baseCurrency = null, string[]|null $targetCurrencies = null, string|Carbon|null $date = null)
 * @method static float|null convert(float $amount, string $fromCurrencySymbolOrCode, string $toCurrencySymbolOrCode, string|Carbon|null $date = null)
 * @method static array<string> getAvailableTargetCurrencies(string $baseCurrencyCode, ?Carbon $date = null)
 *
 * @see ExchangeRateService
 */
class MeridianExchangeRate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ExchangeRateService::class;
    }
}
