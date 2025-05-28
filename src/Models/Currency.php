<?php

declare(strict_types=1);

namespace Denprog\Meridian\Models;

use Denprog\Meridian\Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Class Currency
 *
 * @property int $id
 * @property string $name Currency name (e.g., "US Dollar")
 * @property string $code ISO 4217 Alphabetic code (e.g., "USD")
 * @property string|null $symbol Currency symbol (e.g., "$")
 * @property int $decimal_places Number of decimal places
 * @property bool $enabled Indicates if the currency is active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Country>|Country[] $countries The countries that use this currency.
 * @property-read Collection<int, ExchangeRate>|ExchangeRate[] $ratesAsBase Exchange rates where this currency is the base currency.
 * @property-read Collection<int, ExchangeRate>|ExchangeRate[] $ratesAsTarget Exchange rates where this currency is the target currency.
 * @property-read ExchangeRate|null $latestRateAsBase The latest exchange rate where this currency is the base currency.
 * @property-read ExchangeRate|null $latestRateAsTarget The latest exchange rate where this currency is the target currency.
 */
class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'symbol',
        'decimal_places',
        'enabled',
    ];

    /**
     * Get the countries that use this currency.
     *
     * @return HasMany<Country, $this>
     */
    public function countries(): HasMany
    {
        return $this->hasMany(Country::class, 'currency_code', 'code');
    }

    /**
     * Get the exchange rates where this currency is the base currency.
     *
     * @return HasMany<ExchangeRate, $this>
     */
    public function ratesAsBase(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'base_currency_code', 'code');
    }

    /**
     * Get the exchange rates where this currency is the target currency.
     *
     * @return HasMany<ExchangeRate, $this>
     */
    public function ratesAsTarget(): HasMany
    {
        return $this->hasMany(ExchangeRate::class, 'target_currency_code', 'code');
    }

    /**
     * Get the latest exchange rate where this currency is the base currency.
     *
     * @return HasOne<ExchangeRate, $this>
     */
    public function latestRateAsBase(): HasOne
    {
        return $this->hasOne(ExchangeRate::class, 'base_currency_code', 'code')->latestOfMany('rate_date');
    }

    /**
     * Get the latest exchange rate where this currency is the target currency.
     *
     * @return HasOne<ExchangeRate, $this>
     */
    public function latestRateAsTarget(): HasOne
    {
        return $this->hasOne(ExchangeRate::class, 'target_currency_code', 'code')->latestOfMany('rate_date');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<Currency>
     */
    protected static function newFactory(): Factory
    {
        return CurrencyFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'enabled' => 'boolean',
        ];
    }
}
