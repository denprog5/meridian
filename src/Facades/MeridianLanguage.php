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
 * @method static string name() Get the name of the current language.
 * @method static string code() Get the ISO 639-1 code of the current language.
 * @method static string|null nativeName() Get the native name of the current language.
 * @method static string defaultName() Get the name of the default language.
 * @method static string defaultCode() Get the ISO 639-1 code of the default language.
 * @method static string|null defaultNativeName() Get the native name of the default language.
 * @method static Collection<int, Language> all(bool $activeOnly = true, bool $useCache = true, ?int $cacheTtlMinutes = null) Get all languages.
 * @method static Language|null findByCode(string $code, bool $activeOnly = false, bool $useCache = true, ?int $cacheTtlMinutes = null) Find a language by its code.
 * @method static string|null detectBrowserLanguage() Detect the user's preferred language from browser headers.
 * @method static string|null detectBrowserLocale() Detect the user's preferred locale from browser headers.
 * @method static Language setByBrowserLanguage() Set the language based on browser preferences.
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
