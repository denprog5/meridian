<?php

declare(strict_types=1);

namespace Denprog\Meridian\Models;

use Denprog\Meridian\Database\Factories\ExchangeRateFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Currency
 *
 * @property int $id
 * @property string $base_currency_code
 * @property string $target_currency_code
 * @property float $rate
 * @property Carbon $rate_date
 * @property Carbon|null $created_at
 * @property-read Currency|null $baseCurrency
 * @property-read Currency|null $targetCurrency
 */
class ExchangeRate extends Model
{
    /** @use HasFactory<ExchangeRateFactory> */
    use HasFactory;

    public const ?string UPDATED_AT = null;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'exchange_rates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'base_currency_code',
        'target_currency_code',
        'rate',
        'rate_date',
    ];

    /**
     * Get the base currency for the exchange rate.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function baseCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'base_currency_code', 'code');
    }

    /**
     * Get the target currency for the exchange rate.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function targetCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'target_currency_code', 'code');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<ExchangeRate>
     */
    protected static function newFactory(): Factory
    {
        return ExchangeRateFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'rate_date' => 'datetime:Y-m-d',
            'created_at' => 'datetime',
        ];
    }
}
