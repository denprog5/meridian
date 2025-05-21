<?php

declare(strict_types=1);

namespace Denprog\Meridian\Facades;

use Denprog\Meridian\Services\CurrencyService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Database\Eloquent\Collection getAllCurrencies(bool $useCache = true, int $cacheTtlMinutes = 60)
 * @method static \Denprog\Meridian\Models\Currency|null findCurrencyById(int $id, bool $useCache = true, int $cacheTtlMinutes = 60)
 * @method static \Denprog\Meridian\Models\Currency|null findCurrencyByIsoAlphaCode(string $isoAlphaCode, bool $useCache = true, int $cacheTtlMinutes = 60)
 * @method static \Denprog\Meridian\Models\Currency|null findCurrencyByIsoNumericCode(string $isoNumericCode, bool $useCache = true, int $cacheTtlMinutes = 60)
 *
 * @see CurrencyService
 */
class Currencies extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CurrencyService::class;
    }
}
