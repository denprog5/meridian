<?php

declare(strict_types=1);

namespace Denprog\Meridian\Models;

use Denprog\Meridian\Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
     * Create a new factory instance for the model.
     *
     * @return Factory<Language>
     */
    protected static function newFactory(): Factory
    {
        return LanguageFactory::new();
    }
}
