<?php

declare(strict_types=1);

namespace Denprog\Meridian\Models;

use Denprog\Meridian\Database\Factories\CountryLanguageFactory;
use Denprog\Meridian\Enums\CountryLanguageStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Represents the pivot table linking countries and languages.
 *
 * @property string $country_code ISO 3166-1 alpha-2 country code.
 * @property string $language_code ISO 639-1 language code.
 * @property CountryLanguageStatusEnum|null $status The status of the language in the country.
 * @property-read Country $country The related country.
 * @property-read Language $language The related language.
 */
class CountryLanguage extends Pivot
{
    /** @use HasFactory<CountryLanguageFactory> */
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'country_language';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'country_code',
        'language_code',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'status' => CountryLanguageStatusEnum::class,
    ];

    /**
     * Get the country associated with the record.
     *
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'iso_alpha_2');
    }

    /**
     * Get the language associated with this pivot entry.
     *
     * @return BelongsTo<Language, $this>
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_code', 'code');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<CountryLanguage>
     */
    protected static function newFactory(): Factory
    {
        return CountryLanguageFactory::new();
    }
}
