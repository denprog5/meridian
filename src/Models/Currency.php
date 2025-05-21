<?php

declare(strict_types=1);

namespace Denprog\Meridian\Models;

use Denprog\Meridian\Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Class Currency
 *
 * @property int $id
 * @property string $name Currency name (e.g., "US Dollar")
 * @property string $code ISO 4217 Alphabetic code (e.g., "USD")
 * @property string|null $iso_numeric ISO 4217 Numeric code (e.g., "840")
 * @property string|null $symbol Currency symbol (e.g., "$")
 * @property int $decimal_places Number of decimal places
 * @property bool $enabled Indicates if the currency is active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
        'iso_numeric',
        'symbol',
        'decimal_places',
        'enabled',
    ];

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

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<Currency>
     */
    protected static function newFactory(): Factory
    {
        return CurrencyFactory::new();
    }
}
