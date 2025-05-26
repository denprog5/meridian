<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services;

use Denprog\Meridian\Models\Language;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class LanguageService
{
    public const string SESSION_KEY_USER_LANGUAGE = 'meridian.user_language_code';

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

        $defaultCode = Config::string('meridian.default_language_code', 'en');
        $language = $this->findByCode($defaultCode);

        if (! $language instanceof Language && $defaultCode !== 'en') {
            $language = $this->findByCode('en');
        }

        /** @var Language $language */
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
        $preferredLanguages = request()->getLanguages();

        foreach ($preferredLanguages as $lang) {
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
}
