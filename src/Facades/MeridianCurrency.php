<?php

declare(strict_types=1);

namespace Denprog\Meridian\Facades;

use Denprog\Meridian\Contracts\CurrencyServiceContract;
use Denprog\Meridian\Models\Currency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for accessing currency-related services.
 *
 * @method static Currency baseCurrency() Get the configured base currency model.
 * @method static Currency get() Get the current display currency from session or fallback to base currency.
 * @method static void set(string $currencyCode) Set the display currency in the session.
 * @method static string name() Get the name of the current display currency.
 * @method static string code() Get the ISO 4217 alpha-3 code of the current display currency.
 * @method static string|null symbol() Get the symbol of the current display currency.
 * @method static bool enabled() Get the enabled status of the current display currency.
 * @method static string baseName() Get the name of the base currency.
 * @method static string baseCode() Get the ISO 4217 alpha-3 code of the base currency.
 * @method static string|null baseSymbol() Get the symbol of the base currency.
 * @method static Collection<int, Currency> list() Get the list of configured "active" currency models.
 * @method static Collection<int, Currency> all(bool $useCache = true, int $cacheTtlMinutes = 60) Get all currencies.
 * @method static Currency|null findById(int $id, bool $useCache = true, int $cacheTtlMinutes = 60) Find a currency by its ID.
 * @method static Currency|null findByCode(string $code, bool $useCache = true, int $cacheTtlMinutes = 60) Find a currency by its ISO 4217 alpha-3 code.
 *
 * @see CurrencyServiceContract
 */
class MeridianCurrency extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CurrencyServiceContract::class;
    }
}
