<?php

declare(strict_types=1);

namespace Denprog\Meridian\Models;

use Denprog\Meridian\Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // NEW IMPORT
use Illuminate\Support\Carbon;

/**
 * Class Language
 *
 * @property int $id
 * @property string $name English name of the language
 * @property string $native_name Native name of the language
 * @property string $code Language code (2 characters)
 * @property string $text_direction Text direction ('ltr' or 'rtl')
 * @property bool $is_active Whether the language is active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Country> $countries The countries where this language is spoken.
 */
class Language extends Model
{
    /** @use HasFactory<LanguageFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'native_name',
        'code',
        'text_direction',
        'is_active',
    ];

    /**
     * Get the localized name of the language.
     *
     * @param  string|null  $locale  The locale to use for translation. Defaults to application locale.
     * @return string The localized language name or the default name if no translation exists.
     */
    public function getLocalizedName(?string $locale = null): string
    {
        $key = 'meridian::languages.'.mb_strtolower($this->code);
        $translated = trans(key: $key, locale: $locale);

        if ($translated === $key || is_array($translated)) {
            return $this->name;
        }

        return $translated;
    }

    /**
     * Get the countries where this language is spoken.
     *
     * @return BelongsToMany<Country, $this, CountryLanguage>
     *
     *     @phpstan-return BelongsToMany<Country, $this, CountryLanguage>
     */
    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(
            Country::class,
            'country_language',
            'language_code',
            'country_code',
            'code',
            'iso_alpha_2'
        )
            ->using(CountryLanguage::class)
            ->withPivot('status');
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory<Language>
     */
    protected static function newFactory(): Factory
    {
        return LanguageFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
