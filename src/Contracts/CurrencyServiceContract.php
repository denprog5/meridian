<?php

declare(strict_types=1);

namespace Denprog\Meridian\Contracts;

use Denprog\Meridian\Models\Currency;
use Illuminate\Database\Eloquent\Collection;

interface CurrencyServiceContract
{
    public const SESSION_CURRENCY_CODE = 'meridian.currency_code';

    /**
     * Get the configured base currency model.
     * The base currency is determined by the 'meridian.base_currency_code' config value.
     *
     * @return Currency The base Currency model.
     */
    public function baseCurrency(): Currency;

    /**
     * Gets the current display currency model from session.
     * Falls back to base currency if not set, invalid, or not an active currency.
     *
     * @return Currency The current display Currency model.
     */
    public function get(): Currency;

    /**
     * Sets the display currency in the session.
     * If the provided currency code is not active or invalid, it defaults to the base currency.
     *
     * @param string $currencyCode The ISO 4217 alpha-3 currency code.
     */
    public function set(string $currencyCode): void;

    /**
     * Get the list of configured "active" currency models.
     * Active currencies are determined by 'meridian.active_currencies' config.
     * If not set or empty, it falls back to a default list of common currencies.
     * Results are cached.
     *
     * @return Collection<int, Currency> A collection of active Currency models.
     */
    public function list(): Collection;

    /**
     * Get all currencies.
     * Results can be optionally retrieved from cache.
     *
     * @param bool $useCache Whether to use cache. Defaults to true.
     * @param int $cacheTtlMinutes Cache Time-To-Live in minutes. Defaults to 60.
     * @return Collection<int, Currency> A collection of all Currency models.
     */
    public function all(bool $useCache = true, int $cacheTtlMinutes = 60): Collection;

    /**
     * Find a currency by its ID.
     * Results can be optionally retrieved from cache.
     *
     * @param int $id The currency ID.
     * @param bool $useCache Whether to use cache. Defaults to true.
     * @param int $cacheTtlMinutes Cache Time-To-Live in minutes. Defaults to 60.
     * @return Currency|null The Currency model if found, otherwise null.
     */
    public function findById(int $id, bool $useCache = true, int $cacheTtlMinutes = 60): ?Currency;

    /**
     * Find a currency by its ISO 4217 alpha-3 code (e.g., 'USD', 'EUR').
     * Results can be optionally retrieved from cache. The code is case-insensitive.
     *
     * @param string $code The ISO 4217 alpha-3 currency code.
     * @param bool $useCache Whether to use cache. Defaults to true.
     * @param int $cacheTtlMinutes Cache Time-To-Live in minutes. Defaults to 60.
     * @return Currency|null The Currency model if found, otherwise null.
     */
    public function findByCode(string $code, bool $useCache = true, int $cacheTtlMinutes = 60): ?Currency;
}
