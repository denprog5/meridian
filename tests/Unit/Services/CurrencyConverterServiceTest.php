<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Services;

use Denprog\Meridian\Contracts\CurrencyServiceContract;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Services\CurrencyConverterService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Config;
use Mockery;

    // For type hinting in tests

beforeEach(function (): void {
    global $currencyConverterService, $currencyServiceMock, $cacheMock;

    // Define mocks directly within beforeEach and assign to $this for Pest context
    // Also assign to global vars for easier access/type-hinting in test closures if not using $this
    $this->currencyServiceMock = Mockery::mock(CurrencyServiceContract::class);
    $this->cacheMock = Mockery::mock(CacheRepository::class);

    // Assign to globals for convenience if preferred in tests, though $this-> is Pest's way
    $currencyServiceMock = $this->currencyServiceMock;
    $cacheMock = $this->cacheMock;

    $currencyConverterService = new CurrencyConverterService(
        $this->currencyServiceMock,
        $this->cacheMock
    );
});

afterEach(function (): void {
    Mockery::close();
});

it('can be instantiated', function (): void {
    global $currencyConverterService;
    expect($currencyConverterService)->toBeInstanceOf(CurrencyConverterService::class);
});

// Further tests for format(), getRate(), convert(), convertBetween() will be added here.

it('formats currency correctly with a specific locale', function (): void {
    global $currencyConverterService;

    $amount = 1234.56;
    $currencyCode = 'USD';
    $locale = 'en_US';

    $formattedAmount = $currencyConverterService->format($amount, $currencyCode, $locale);

    // Note: The exact output can vary slightly based on ICU version and system locale data.
    // For en_US and USD, '$1,234.56' is a common representation.
    // Using a regex to be more flexible with minor variations like non-breaking spaces.
    expect($formattedAmount)->toMatch('/^\$\s?1,234\.56$/');
});

it('formats currency using app.locale when no locale is provided', function (): void {
    global $currencyConverterService;

    $amount = 1234.56;
    $currencyCode = 'EUR';
    $appLocale = 'fr_FR';

    // Mock the config() helper to return our desired app.locale
    // Note: Pest's mocking of global functions might require specific setup or context.
    // If direct mocking of config() is problematic, an alternative is to inject Config repository.
    // For now, assuming direct mocking works or can be adjusted.
    Config::shouldReceive('get')
        ->with('app.locale')
        ->andReturn($appLocale);

    $formattedAmount = $currencyConverterService->format($amount, $currencyCode, null);

    // For fr_FR and EUR, '1 234,56 €' is a common representation (with non-breaking space).
    // Using a regex for flexibility with space variations.
    expect($formattedAmount)->toMatch('/^1\s?234,56\s?€$/');
});

it('formats currency using en_US as fallback when app.locale is invalid or not set', function (): void {
    global $currencyConverterService;

    $amount = 789.10;
    $currencyCode = 'JPY'; // Japanese Yen

    // Scenario 1: app.locale returns null
    Config::shouldReceive('get')->with('app.locale')->andReturn(null);
    $formattedAmountNull = $currencyConverterService->format($amount, $currencyCode, null);
    // JPY with en_US locale should be like '¥789' (no decimals for JPY by default in en_US)
    expect($formattedAmountNull)->toBe('¥789');

    // Scenario 2: app.locale returns empty string
    Config::shouldReceive('get')->with('app.locale')->andReturn('');
    $formattedAmountEmpty = $currencyConverterService->format($amount, $currencyCode, null);
    expect($formattedAmountEmpty)->toBe('¥789');

    // Scenario 3: app.locale returns an invalid/non-string value (e.g. an array)
    Config::shouldReceive('get')->with('app.locale')->andReturn([]);
    $formattedAmountArray = $currencyConverterService->format($amount, $currencyCode, null);
    expect($formattedAmountArray)->toBe('¥789');
});

it('returns null when formatCurrency call fails', function (): void {
    global $currencyConverterService;

    $amount = 100.00;
    $locale = 'en_US';
    // Using an invalid or unrecognized currency code for the formatter
    $invalidCurrencyCode = 'XYZ123';

    $formattedAmount = $currencyConverterService->format($amount, $invalidCurrencyCode, $locale);
    expect($formattedAmount)->toBeNull();
});

// Tests for getRate() method
describe('getRate', function (): void {
    it('returns 1.0 when target and explicit base currencies are the same', function (): void {
        global $currencyConverterService;
        $rate = $currencyConverterService->getRate('USD', 'USD');
        expect($rate)->toBe(1.0);
    });

    it('returns 1.0 when target currency is the same as system base currency and base is null', function (): void {
        global $currencyConverterService, $currencyServiceMock;

        $systemBaseCurrencyCode = 'EUR';
        $mockSystemBaseCurrency = new Currency(['code' => $systemBaseCurrencyCode]);

        $currencyServiceMock->shouldReceive('baseCurrency')
            ->once()
            ->andReturn($mockSystemBaseCurrency);

        $rate = $currencyConverterService->getRate($systemBaseCurrencyCode, null);
        expect($rate)->toBe(1.0);
    });

    it('retrieves rate from cache if available', function (): void {
        global $currencyConverterService, $currencyServiceMock, $cacheMock;

        $targetCurrencyCode = 'GBP';
        $baseCurrencyCode = 'USD';
        $cachedRate = 0.85;
        $date = null; // Not specifying date, so it uses current date for cache key

        // Mock baseCurrency() in case it's called (though for direct cache hit it might not be)
        $mockSystemBaseCurrency = new Currency(['code' => $baseCurrencyCode]);
        $currencyServiceMock->shouldReceive('baseCurrency')->andReturn($mockSystemBaseCurrency); // Allow multiple calls if needed

        $cacheKey = "exchange_rate_{$baseCurrencyCode}_{$targetCurrencyCode}_".now()->toDateString();
        $cacheMock->shouldReceive('get')
            ->with($cacheKey)
            ->once()
            ->andReturn($cachedRate);

        // Ensure findByCode is not called if rate is from cache
        $currencyServiceMock->shouldNotReceive('findByCode');

        $rate = $currencyConverterService->getRate($targetCurrencyCode, $baseCurrencyCode, $date);
        expect($rate)->toBe($cachedRate);
    });

    it('retrieves rate from database and caches it when not in cache', function (): void {
        global $currencyConverterService, $currencyServiceMock, $cacheMock;

        $targetCurrencyCode = 'CAD';
        $baseCurrencyCode = 'USD';
        $dbRate = 1.25;
        $date = null; // Using current date
        $cacheTtl = 1440; // Example TTL

        $mockSystemBaseCurrency = new Currency(['code' => $baseCurrencyCode]);
        $currencyServiceMock->shouldReceive('baseCurrency')->andReturn($mockSystemBaseCurrency);

        $cacheKey = "exchange_rate_{$baseCurrencyCode}_{$targetCurrencyCode}_".now()->toDateString();

        $cacheMock->shouldReceive('get')
            ->with($cacheKey)
            ->once()
            ->andReturn(null); // Cache miss

        $currencyServiceMock->shouldReceive('getExchangeRate')
            ->with($targetCurrencyCode, $baseCurrencyCode, Mockery::type(\Carbon\Carbon::class)) // Expect a Carbon instance for date
            ->once()
            ->andReturn($dbRate);

        Config::shouldReceive('get')
            ->with('meridian.cache.exchange_rate_ttl', 1440) // Default TTL from config
            ->andReturn($cacheTtl);

        $cacheMock->shouldReceive('put')
            ->with($cacheKey, $dbRate, $cacheTtl * 60) // TTL is in minutes, put expects seconds
            ->once();

        $rate = $currencyConverterService->getRate($targetCurrencyCode, $baseCurrencyCode, $date);
        expect($rate)->toBe($dbRate);
    });

    it('returns null if system base currency is not found when baseCurrencyCode is null', function (): void {
        global $currencyConverterService, $currencyServiceMock;

        $targetCurrencyCode = 'JPY';

        $currencyServiceMock->shouldReceive('baseCurrency')
            ->once()
            ->andReturn(null); // System base currency not found

        $rate = $currencyConverterService->getRate($targetCurrencyCode, null);
        expect($rate)->toBeNull();
    });

    it('returns null if exchange rate is not found in database and not in cache', function (): void {
        global $currencyConverterService, $currencyServiceMock, $cacheMock;

        $targetCurrencyCode = 'AUD';
        $baseCurrencyCode = 'USD';
        $date = null;

        $mockSystemBaseCurrency = new Currency(['code' => $baseCurrencyCode]);
        $currencyServiceMock->shouldReceive('baseCurrency')->andReturn($mockSystemBaseCurrency);

        $cacheKey = "exchange_rate_{$baseCurrencyCode}_{$targetCurrencyCode}_".now()->toDateString();
        $cacheMock->shouldReceive('get')
            ->with($cacheKey)
            ->once()
            ->andReturn(null); // Cache miss

        $currencyServiceMock->shouldReceive('getExchangeRate')
            ->with($targetCurrencyCode, $baseCurrencyCode, Mockery::type(\Carbon\Carbon::class))
            ->once()
            ->andReturn(null); // DB miss

        $cacheMock->shouldNotReceive('put'); // Should not attempt to cache a null rate

        $rate = $currencyConverterService->getRate($targetCurrencyCode, $baseCurrencyCode, $date);
        expect($rate)->toBeNull();
    });
});

// Tests for convert() method
describe('convert', function (): void {
    it('returns null if active display currency is not found', function (): void {
        global $currencyConverterService, $currencyServiceMock;

        $currencyServiceMock->shouldReceive('get') // This is for getActiveDisplayCurrency()
            ->once()
            ->andReturn(null);

        $result = $currencyConverterService->convert(100.00);
        expect($result)->toBeNull();
    });

    it('returns null if system base currency is not found', function (): void {
        global $currencyConverterService, $currencyServiceMock;

        $activeDisplayCurrency = new Currency(['code' => 'EUR']);
        $currencyServiceMock->shouldReceive('get')->once()->andReturn($activeDisplayCurrency);
        $currencyServiceMock->shouldReceive('baseCurrency')->once()->andReturn(null);

        $result = $currencyConverterService->convert(100.00);
        expect($result)->toBeNull();
    });

    it('returns original amount if active display currency is same as base currency and not formatted', function (): void {
        global $currencyConverterService, $currencyServiceMock;

        $sameCurrencyCode = 'USD';
        $amount = 150.75;

        $activeDisplayCurrency = new Currency(['code' => $sameCurrencyCode]);
        $systemBaseCurrency = new Currency(['code' => $sameCurrencyCode]);

        $currencyServiceMock->shouldReceive('get')->once()->andReturn($activeDisplayCurrency);
        $currencyServiceMock->shouldReceive('baseCurrency')->once()->andReturn($systemBaseCurrency);

        $result = $currencyConverterService->convert($amount, false); // returnFormatted = false
        expect($result)->toBe($amount);
    });

    it('returns formatted amount if active display currency is same as base currency and formatted', function (): void {
        global $currencyConverterService, $currencyServiceMock;

        $sameCurrencyCode = 'EUR';
        $amount = 200.00;
        $locale = 'de_DE'; // Example locale
        $expectedFormattedAmount = '200,00 €'; // Example for de_DE, EUR. Note non-breaking space.

        $activeDisplayCurrency = new Currency(['code' => $sameCurrencyCode]);
        $systemBaseCurrency = new Currency(['code' => $sameCurrencyCode]);

        $currencyServiceMock->shouldReceive('get')->once()->andReturn($activeDisplayCurrency);
        $currencyServiceMock->shouldReceive('baseCurrency')->once()->andReturn($systemBaseCurrency);

        // We don't need to mock format() itself, but we need to ensure config for locale is set if format relies on it.
        Config::shouldReceive('get')->with('app.locale')->andReturn('en_US'); // Default app locale for fallback

        $result = $currencyConverterService->convert($amount, true, $locale); // returnFormatted = true

        // Using a regex to be more flexible with minor variations like non-breaking spaces.
        expect($result)->toMatch('/^200,00\s?€$/');
    });
});
