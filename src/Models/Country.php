<?php

declare(strict_types=1);

namespace Denprog\Meridian\Models;

use Denprog\Meridian\Database\Factories\CountryFactory;
use Denprog\Meridian\Enums\Continent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Country
 *
 * @property int $id
 * @property string $continent_code
 * @property string $name
 * @property string|null $official_name
 * @property string|null $native_name
 * @property string $iso_alpha_2 ISO 3166-1 alpha-2 code
 * @property string $iso_alpha_3 ISO 3166-1 alpha-3 code
 * @property string|null $iso_numeric ISO 3166-1 numeric code
 * @property string|null $phone_code International phone calling code(s), comma-separated if multiple
 * @property string|null $currency_code ISO 4217 currency code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Continent $continent The continent enum instance.
 * @property-read Currency|null $currency The currency of the country.
 */
class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'official_name',
        'native_name',
        'iso_alpha_2',
        'iso_alpha_3',
        'iso_numeric',
        'phone_code',
        'continent_code',
        'currency_code',
    ];

    /**
     * Get the localized name of the country.
     *
     * @param  string|null  $locale  The locale to use for translation. Defaults to application locale.
     * @return string The localized country name or the default name if no translation exists.
     */
    public function getLocalizedName(?string $locale = null): string
    {
        $key = 'meridian::countries.'.mb_strtolower($this->iso_alpha_2);
        $translated = trans($key, [], $locale);

        if ($translated === $key || is_array($translated)) {
            return $this->name;
        }

        return $translated;
    }

    /**
     * Get the currency of the country.
     *
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<Country>
     */
    protected static function newFactory(): Factory
    {
        return CountryFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'continent_code' => Continent::class,
        ];
    }
}
