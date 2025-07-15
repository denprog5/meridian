<?php

declare(strict_types=1);

namespace Denprog\Meridian\Facades;

use Denprog\Meridian\Contracts\UpdateExchangeRateContract;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void update()
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
