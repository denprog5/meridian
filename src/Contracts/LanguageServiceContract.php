<?php

declare(strict_types=1);

namespace Denprog\Meridian\Contracts;

use Denprog\Meridian\Models\Language;
use Illuminate\Database\Eloquent\Collection;

interface LanguageServiceContract
{
    public const string SESSION_KEY_USER_LANGUAGE = 'meridian.user_language_code';

    public const string CACHE_KEY_LANGUAGE_CODE_PREFIX = 'language.code.';

    /**
     * Get the current active language based on session, browser detection, or default.
     *
     * @return Language The active language.
     */
    public function get(): Language;

    /**
     * Set the user's selected language in the session.
     *
     * @param  string  $code  The language code (e.g., 'en', 'de').
     */
    public function set(string $code): void;

    /**
     * Get the default language from configuration.
     *
     * @return Language The default language.
     */
    public function default(): Language;

    /**
     * Get all languages, optionally filtered by active status and configuration.
     *
     * @param  bool  $useCache  Whether to use cache.
     * @param  int|null  $cacheTtlMinutes  Cache TTL in minutes (uses config if available).
     * @return Collection<int, Language>
     */
    public function all(bool $useCache = true, ?int $cacheTtlMinutes = null): Collection;

    /**
     * Find a language by its code (e.g., 'en', 'de').
     *
     * @param  string  $code  The language code (case-insensitive).
     * @param  bool  $useCache  Whether to use cache.
     * @param  int|null  $cacheTtlMinutes  Cache TTL in minutes (uses config if available).
     * @return Language|null The found language or null.
     */
    public function findByCode(string $code, bool $useCache = true, ?int $cacheTtlMinutes = null): ?Language;

    /**
     * Detects the user's preferred language from the browser's Accept-Language header.
     *
     * @return string|null The detected 2-letter language code (e.g., 'en', 'de') or null if not detected/configured.
     */
    public function detectBrowserLanguage(): ?string;

    /**
     * Sets the active language based on browser detection, falling back to default.
     */
    public function setByBrowserLanguage(): void;
}
