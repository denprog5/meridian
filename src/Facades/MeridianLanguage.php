<?php

declare(strict_types=1);

namespace Denprog\Meridian\Facades;

use Denprog\Meridian\Models\Language;
use Denprog\Meridian\Services\LanguageService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @see LanguageService
 *
 * @method static Language get()
 * @method static void set(string $code)
 * @method static Language default()
 * @method static Collection<int, Language> all(bool $activeOnly = true, bool $useCache = true, ?int $cacheTtlMinutes = null)
 * @method static Language|null findByCode(string $code, bool $activeOnly = false, bool $useCache = true, ?int $cacheTtlMinutes = null)
 * @method static string|null detectBrowserLanguage()
 * @method static Language setByBrowserLanguage()
 */
class MeridianLanguage extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return LanguageService::class;
    }
}
