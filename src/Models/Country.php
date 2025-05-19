<?php

declare(strict_types=1);

namespace Denprog\Meridian\Models;

use Denprog\Meridian\Database\Factories\CountryFactory;
use Denprog\Meridian\Enums\Continent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
 * @property bool $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'continent_code' => Continent::class,
            'enabled' => 'boolean',
        ];
    }

    /**
     * Create a new factory instance for the model.
     * @return Factory<Country>
     */
    protected static function newFactory(): Factory
    {
        return CountryFactory::new();
    }

}
