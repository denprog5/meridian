<?php

declare(strict_types=1);

namespace Denprog\Meridian\Facades;

use Denprog\Meridian\Contracts\LanguageServiceContract;
use Denprog\Meridian\Models\Language;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for accessing language-related services.
 *
 * @method static Language get() Get the user's selected language from session.
 * @method static void set(string $code) Set the user's selected language in the session.
 * @method static Language default() Get the default language from configuration.
 * @method static Collection<int, Language> all(bool $useCache = true, ?int $cacheTtlMinutes = null) Get all active languages.
 * @method static Language|null findByCode(string $code, bool $useCache = true, ?int $cacheTtlMinutes = null) Find a language by its code.
 * @method static string|null detectBrowserLanguage() Detect the user's preferred language from browser headers.
 * @method static string detectBrowserLocale() Detect the user's preferred locale from browser headers.
 * @method static void setByBrowserLanguage() Set the language based on browser preferences.
 *
 * @see LanguageServiceContract
 */
class MeridianLanguage extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LanguageServiceContract::class;
    }
}
