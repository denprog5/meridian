<?php

declare(strict_types=1);

namespace Denprog\Meridian\Facades;

use Denprog\Meridian\Contracts\CurrencyConverterContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for currency conversion and exchange rate operations.
 *
 * @method static float|string convert(float|int $amount, bool $returnFormatted = false, ?string $locale = null) Convert amount from base currency to current display currency.
 * @method static float|string convertBetween(float|int $amount, string $toCurrencyCode, ?string $fromCurrencyCode = null, bool $returnFormatted = false, string|Carbon|null $date = null, ?string $locale = null) Convert amount between two specified currencies.
 * @method static string format(float $amount, string $currencyCode, ?string $locale = null) Format an amount with the specified currency.
 * @method static float|string getRate(string $targetCurrencyCode, ?string $baseCurrencyCode = null, string|Carbon|null $date = null) Get the exchange rate between two currencies.
 * @method static array<string, float>|null getRates(string $targetCurrencyCode, ?string $baseCurrencyCode = null, string|Carbon|null $date = null) Get multiple exchange rates.
 *
 * @see CurrencyConverterContract
 */
class MeridianExchangeRate extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CurrencyConverterContract::class;
    }
}
