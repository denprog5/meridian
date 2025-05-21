<?php

declare(strict_types=1);

namespace Denprog\Meridian\Facades;

use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Services\CurrencyService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection<int, Currency> getAllCurrencies(bool $useCache = true, int $cacheTtlMinutes = 60)
 * @method static Currency|null findCurrencyById(int $id, bool $useCache = true, int $cacheTtlMinutes = 60)
 * @method static Currency|null findCurrencyByIsoAlphaCode(string $isoAlphaCode, bool $useCache = true, int $cacheTtlMinutes = 60)
 * @method static Currency|null findCurrencyByIsoNumericCode(string $isoNumericCode, bool $useCache = true, int $cacheTtlMinutes = 60)
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
