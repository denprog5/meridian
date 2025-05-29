<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services;

use Denprog\Meridian\Contracts\LanguageServiceContract;
use Denprog\Meridian\Models\Language;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use RuntimeException;

final class LanguageService implements LanguageServiceContract
{
    public const string SESSION_KEY_USER_LANGUAGE = LanguageServiceContract::SESSION_KEY_USER_LANGUAGE;

    private ?Language $language = null;

    private ?Language $defaultLanguage = null;

    /**
     * Get the current active language based on session, browser detection, or default.
     */
    public function get(): Language
    {
        if ($this->language instanceof Language) {
            return $this->language;
        }

        $language = null;

        $sessionLanguageCode = Session::get(self::SESSION_KEY_USER_LANGUAGE);
        if (! empty($sessionLanguageCode) && is_string($sessionLanguageCode)) {
            $language = $this->findByCode($sessionLanguageCode);
            if (! $language instanceof Language) {
                Session::forget(self::SESSION_KEY_USER_LANGUAGE);
            }
        }

        if (! $language instanceof Language) {
            $language = $this->default();
        }

        $this->language = $language;

        return $this->language;
    }

    /**
     * Set the user's selected language in the session.
     */
    public function set(string $code): void
    {
        $code = mb_strtolower($code);
        $language = $this->findByCode($code);

        if (! $language instanceof Language) {
            return;
        }

        $this->language = $language;
        Session::put(self::SESSION_KEY_USER_LANGUAGE, $language->code);
    }

    /**
     * Get the default language from configuration.
     */
    public function default(): Language
    {
        if ($this->defaultLanguage instanceof Language) {
            return $this->defaultLanguage;
        }

        $configuredCode = Config::get('meridian.default_language_code');
        $language = null;

        if (is_string($configuredCode) && ($configuredCode !== '' && $configuredCode !== '0')) {
            $language = $this->findByCode(mb_strtolower($configuredCode));
        }

        if (! $language instanceof Language) {
            $language = $this->findByCode('en');
        }

        if (! $language instanceof Language) {
            throw new RuntimeException("Unable to determine default language. Fallback 'en' not found.");
        }

        $this->defaultLanguage = $language;

        return $this->defaultLanguage;
    }

    /**
     * Get all languages, optionally filtered by active status and configuration.
     *
     * @param  bool  $useCache  Whether to use cache.
     * @param  int|null  $cacheTtlMinutes  Cache TTL in minutes (uses config if available).
     * @return Collection<int, Language>
     */
    public function all(bool $useCache = true, ?int $cacheTtlMinutes = null): Collection
    {
        $cacheKey = 'languages.all';

        $ttl = $cacheTtlMinutes ?? Config::integer('meridian.cache_lifetimes.languages', 60) * 60;

        if ($useCache) {
            /** @var Collection<int, Language>|null $cachedLanguages */
            $cachedLanguages = Cache::get($cacheKey);
            if ($cachedLanguages !== null) {
                return $cachedLanguages;
            }
        }

        $query = Language::query()->where('is_active', true);

        /** @var Collection<int, Language> $languages */
        $languages = $query->orderBy('name')->get();

        if ($useCache) {
            Cache::put($cacheKey, $languages, $ttl);
        }

        return $languages;
    }

    /**
     * Find a language by its code (e.g., 'en', 'de').
     *
     * @param  string  $code  The language code (case-insensitive).
     * @param  bool  $useCache  Whether to use cache.
     * @param  int|null  $cacheTtlMinutes  Cache TTL in minutes (uses config if available).
     */
    public function findByCode(string $code, bool $useCache = true, ?int $cacheTtlMinutes = null): ?Language
    {
        $code = mb_strtolower($code);
        $cacheKey = 'language.code.'.$code;
        $ttl = $cacheTtlMinutes ?? Config::integer('meridian.cache_lifetimes.languages', 60) * 60;

        if ($useCache) {
            /** @var Language|null|false $cachedLanguage False if explicitly cached as not found */
            $cachedLanguage = Cache::get($cacheKey);
            if ($cachedLanguage !== null) {
                return $cachedLanguage === false ? null : $cachedLanguage;
            }
        }

        $query = Language::query()->where('code', $code);

        /** @var Language|null $language */
        $language = $query->first();

        if ($useCache) {
            Cache::put($cacheKey, $language ?? false, $ttl);
        }

        return $language;
    }

    /**
     * Detects the user's preferred language from the browser's Accept-Language header.
     *
     * @return string|null The detected 2-letter language code (e.g., 'en', 'de') or null if not detected/configured.
     */
    public function detectBrowserLanguage(): ?string
    {
        $acceptLanguageHeader = request()->server('HTTP_ACCEPT_LANGUAGE');

        if (empty($acceptLanguageHeader)) {
            return null;
        }

        $preferredLanguages = request()->getLanguages();

        foreach ($preferredLanguages as $lang) {
            if (in_array(mb_trim($lang), ['', '0'], true)) {
                continue;
            }
            $primaryCode = mb_strtolower(mb_substr($lang, 0, 2));

            if (mb_strlen($primaryCode) === 2) {
                return $primaryCode;
            }
        }

        return null;
    }

    /**
     * Sets the active language based on browser detection, falling back to default.
     */
    public function setByBrowserLanguage(): void
    {
        $browserLanguageCode = $this->detectBrowserLanguage();
        $language = false;

        if ($browserLanguageCode !== null && $browserLanguageCode !== '' && $browserLanguageCode !== '0') {
            $language = $this->findByCode($browserLanguageCode);
        }

        if (! $language) {
            $language = $this->default();
        }

        $this->language = $language;
        Session::put(self::SESSION_KEY_USER_LANGUAGE, $language->code);

    }

    /**
     * Gets the user's most preferred locale from the browser's Accept-Language header.
     * Returns 'en_US' if no preferred locale can be determined.
     * The returned locale is normalized to 'll_RR' or 'll' format.
     *
     * @return string The preferred locale (e.g., 'en_US', 'de_CH', 'fr') or 'en_US'.
     */
    public function detectBrowserLocale(): string
    {
        $defaultLocale = 'en_US';
        $preferredLocales = request()->getLanguages();

        if (empty($preferredLocales) || in_array(mb_trim($preferredLocales[0]), ['', '0'], true)) {
            return $defaultLocale;
        }

        $rawLocale = $preferredLocales[0];

        $parts = preg_split('/[-_]/', $rawLocale, 2);
        $lang = isset($parts[0]) ? mb_strtolower(mb_trim($parts[0])) : null;

        if ($lang === null || $lang === '' || $lang === '0' || mb_strlen($lang) !== 2 || ! ctype_alpha($lang)) {
            return $defaultLocale;
        }

        $region = (isset($parts[1])) ? mb_strtoupper(mb_trim($parts[1])) : null;

        if ($region && mb_strlen($region) === 2 && ctype_alpha($region)) {
            return "{$lang}_$region";
        }

        return $lang;
    }
}
