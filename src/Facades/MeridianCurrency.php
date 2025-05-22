<?php

declare(strict_types=1);

namespace Denprog\Meridian\Facades;

use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Services\CurrencyService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Currency baseCurrency()
 * @method static Collection<int, Currency> list()
 * @method static Currency get()
 * @method static void set(string $currencyCode)
 * @method static Collection<int, Currency> all(bool $useCache = true, int $cacheTtlMinutes = 60)
 * @method static Currency|null findById(int $id, bool $useCache = true, int $cacheTtlMinutes = 60)
 * @method static Currency|null findByCode(string $code, bool $useCache = true, int $cacheTtlMinutes = 60)
 *
 * @see CurrencyService
 */
class MeridianCurrency extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CurrencyService::class;
    }
}
