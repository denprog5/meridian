<?php

declare(strict_types=1);

namespace Denprog\Meridian\Services;

use Denprog\Meridian\Contracts\CurrencyConverterContract;
use Denprog\Meridian\Contracts\CurrencyServiceContract;
use Denprog\Meridian\Contracts\LanguageServiceContract;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Models\ExchangeRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use NumberFormatter;

/**
 * Service for converting and formatting currency amounts, and retrieving exchange rates.
 */
final class CurrencyConverterService implements CurrencyConverterContract
{
    private float $exchangeRateValue;

    private Currency $currency;

    private Currency $baseCurrency;

    private NumberFormatter $formatter;

    /**
     * CurrencyConverterService constructor.
     */
    public function __construct(
        private readonly CurrencyServiceContract $currencyService,
        private readonly LanguageServiceContract $languageService,

    ) {
        $this->setDefaults();
    }

    public function setDefaults(): void
    {
        $this->currency = $this->currencyService->get();
        $this->baseCurrency = $this->currencyService->baseCurrency();
        $defaultLocale = $this->languageService->detectBrowserLocale();
        $exchangeRate = ExchangeRate::query()
            ->where('base_currency_code', $this->baseCurrency->code)
            ->where('target_currency_code', $this->currency->code)
            ->latest('rate_date')
            ->first();

        if ($this->currency->code === $this->baseCurrency->code) {
            $this->exchangeRateValue = 1.0;
        } else {
            $this->exchangeRateValue = $exchangeRate ? $exchangeRate->rate : 1.0;
        }

        $this->formatter = new NumberFormatter($defaultLocale, NumberFormatter::CURRENCY);
    }

    /**
     * {@inheritdoc}
     */
    public function convert(float|int $amount, bool $returnFormatted = false, ?string $locale = null): float|string
    {
        $convertedAmount = round($amount * $this->exchangeRateValue);

        if ($returnFormatted) {
            return $this->format($convertedAmount, $this->currency->code, $locale);
        }

        return $convertedAmount;
    }

    /**
     * {@inheritdoc}
     */
    public function convertBetween(float|int $amount, string $toCurrencyCode, ?string $fromCurrencyCode = null, bool $returnFormatted = false, string|Carbon|null $date = null, ?string $locale = null): float|string
    {
        $exchangeRate = $this->getRate($toCurrencyCode, $fromCurrencyCode, $date);
        $convertedAmount = round($amount * $exchangeRate);

        if ($returnFormatted) {
            return $this->format($convertedAmount, $this->currency->code, $locale);
        }

        return $convertedAmount;
    }

    /**
     * {@inheritdoc}
     */
    public function format(float $amount, string $currencyCode, ?string $locale = null): string
    {
        $formatter = $locale !== null && $locale !== '' && $locale !== '0' ? new NumberFormatter($locale, NumberFormatter::CURRENCY) : $this->formatter;

        $formatAmount = $formatter->formatCurrency($amount, $currencyCode);

        if ($formatAmount === false) {
            return number_format($amount, 2).' '.$currencyCode;
        }

        return $formatAmount;
    }

    /**
     * {@inheritdoc}
     */
    public function getRate(string $targetCurrencyCode, ?string $baseCurrencyCode = null, string|Carbon|null $date = null): float
    {
        $defaultRate = 1.0;
        if ($baseCurrencyCode === null || $baseCurrencyCode === '' || $baseCurrencyCode === '0') {
            $baseCurrencyCode = $this->baseCurrency->code;
        }

        if ($targetCurrencyCode === $baseCurrencyCode) {
            return $defaultRate;
        }

        if (empty($date)) {
            $date = Carbon::now();
        } elseif (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $dateString = $date->format('Y-m-d');
        $cacheKey = "meridian.exchange_rate.$baseCurrencyCode.$targetCurrencyCode.$dateString";

        $cachedRate = Cache::get($cacheKey);
        if (is_numeric($cachedRate)) {
            return (float) $cachedRate;
        }

        $exchangeRate = ExchangeRate::query()
            ->where('base_currency_code', $baseCurrencyCode)
            ->where('target_currency_code', $targetCurrencyCode)
            ->where('rate_date', $date)
            ->first();

        if ($exchangeRate) {
            $cacheTtl = config()->integer('meridian.cache.exchange_rates', 1800);
            Cache::put($cacheKey, $exchangeRate->rate, $cacheTtl);

            return $exchangeRate->rate;
        }
        Log::info("[Meridian] No exchange rate found for $baseCurrencyCode to $targetCurrencyCode on $dateString. Returned default rate. Update the exchange rate first.");

        return $defaultRate;
    }

    /**
     * @return array<string, float>|null
     */
    public function getRates(string $targetCurrencyCode, ?string $baseCurrencyCode = null, string|Carbon|null $date = null): ?array
    {
        if ($baseCurrencyCode === null || $baseCurrencyCode === '' || $baseCurrencyCode === '0') {
            $baseCurrencyCode = $this->baseCurrency->code;
        }

        if (empty($date)) {
            $date = Carbon::now();
        } elseif (is_string($date)) {
            $date = Carbon::parse($date);
        }

        $dateString = $date->format('Y-m-d');
        $cacheKey = "meridian.exchange_rates.$baseCurrencyCode.$targetCurrencyCode.$dateString";

        /** @var array<string, float>|null $cachedRates */
        $cachedRates = Cache::get($cacheKey);
        if (! empty($cachedRates)) {
            return $cachedRates;
        }

        $exchangeRates = ExchangeRate::query()
            ->where('base_currency_code', $baseCurrencyCode)
            ->where('target_currency_code', $targetCurrencyCode)
            ->where('rate_date', $date)
            ->pluck('rate', 'target_currency_code');

        if ($exchangeRates->count() > 0) {
            Cache::put($cacheKey, $exchangeRates, 3600);

            /** @var array<string, float> */
            return $exchangeRates->toArray();
        }

        return null;
    }
}
