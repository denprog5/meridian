<?php

declare(strict_types=1);

namespace Denprog\Meridian\Models;

use Denprog\Meridian\Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 */
class Currency extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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
