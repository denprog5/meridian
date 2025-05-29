<?php

declare(strict_types=1);

namespace Denprog\Meridian\Facades;

use Denprog\Meridian\Contracts\CurrencyConverterContract;
use Illuminate\Support\Facades\Facade;

/**
 * @method static float|null convert(float $amount, string $fromCurrencyCode, string $toCurrencyCode, \Illuminate\Support\Carbon|null $date = null)
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
