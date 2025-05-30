<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Services;

use Denprog\Meridian\Contracts\CurrencyServiceContract;
use Denprog\Meridian\Contracts\LanguageServiceContract;
use Denprog\Meridian\Database\Factories\CountryFactory;
use Denprog\Meridian\Database\Factories\CurrencyFactory;
use Denprog\Meridian\Database\Factories\ExchangeRateFactory;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Services\CurrencyConverterService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Locale;
use Mockery\MockInterface;
use NumberFormatter;
use ReflectionClass;

beforeEach(function (): void {
    Config::set('meridian.base_currency_code', 'USD');
    Config::set('meridian.active_currency_codes', ['USD', 'EUR', 'GBP']);

    CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'USD', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'PLN', 'enabled' => true]);
    CountryFactory::new()->create(['iso_alpha_2' => 'DE', 'name' => 'Germany', 'currency_code' => 'EUR']);
    CountryFactory::new()->create(['iso_alpha_2' => 'US', 'name' => 'United States', 'currency_code' => 'USD']);
    ExchangeRateFactory::new()->create(['base_currency_code' => 'USD', 'target_currency_code' => 'EUR', 'rate' => 0.9]);
    ExchangeRateFactory::new()->create(['base_currency_code' => 'EUR', 'target_currency_code' => 'USD', 'rate' => 1.1]);
    ExchangeRateFactory::new()->create(['base_currency_code' => 'USD', 'target_currency_code' => 'PLN', 'rate' => 4.0]);
});

test('initializes with default values when display currency is base currency', function (): void {
    // Arrange
    /** @var Currency|MockInterface $mockCurrency */
    $mockCurrency = mock(Currency::class);

    $mockCurrency->shouldReceive('setAttribute')->zeroOrMoreTimes()->andReturnSelf();
    $mockCurrency->shouldReceive('getAttribute')->with('code')->zeroOrMoreTimes()->andReturn('USD');
    $mockCurrency->shouldReceive('getAttribute')->with('decimal_places')->zeroOrMoreTimes()->andReturn(2);
    $mockCurrency->shouldReceive('getAttribute')->with('symbol')->zeroOrMoreTimes()->andReturn('$');
    $mockCurrency->shouldReceive('getAttribute')->with('enabled')->zeroOrMoreTimes()->andReturn(true);
    $mockCurrency->shouldReceive('__get')->with('code')->zeroOrMoreTimes()->andReturn('USD');
    $mockCurrency->shouldReceive('__get')->with('decimal_places')->zeroOrMoreTimes()->andReturn(2);
    $mockCurrency->shouldReceive('__get')->with('symbol')->zeroOrMoreTimes()->andReturn('$');
    $mockCurrency->shouldReceive('__get')->with('enabled')->zeroOrMoreTimes()->andReturn(true);

    /** @var CurrencyServiceContract&MockInterface $mockCurrencyService */
    $mockCurrencyService = mock(CurrencyServiceContract::class);
    $mockCurrencyService->shouldReceive('baseCurrency')->once()->andReturn($mockCurrency);
    $mockCurrencyService->shouldReceive('get')->once()->withNoArgs()->andReturn($mockCurrency);

    /** @var LanguageServiceContract&MockInterface $mockLanguageService */
    $mockLanguageService = mock(LanguageServiceContract::class);
    $mockLanguageService->shouldReceive('detectBrowserLocale')->once()->andReturn('en_US');

    $service = new CurrencyConverterService($mockCurrencyService, $mockLanguageService);

    // Assert
    $reflection = new ReflectionClass($service);

    $baseCurrencyProp = $reflection->getProperty('baseCurrency');
    expect($baseCurrencyProp->getValue($service))->toBe($mockCurrency);

    $displayCurrencyProp = $reflection->getProperty('currency');
    expect($displayCurrencyProp->getValue($service))->toBe($mockCurrency);

    $exchangeRateValueProp = $reflection->getProperty('exchangeRateValue');
    expect($exchangeRateValueProp->getValue($service))->toBe(1.0);

    $formatterProp = $reflection->getProperty('formatter');
    $formatter = $formatterProp->getValue($service);
    expect($formatter)->toBeInstanceOf(NumberFormatter::class)
        ->and($formatter->getLocale(Locale::VALID_LOCALE))->toBe('en_US')
        ->and($formatter->getAttribute(NumberFormatter::MAX_FRACTION_DIGITS))->toBe($mockCurrency->decimal_places);
});

it('returns converted amount', function (): void {
    $amount = 100.0;
    $convertAmount = 90.0;

    $sessionKey = CurrencyServiceContract::SESSION_CURRENCY_CODE;

    Session::expects('get')
        ->once()
        ->with($sessionKey)
        ->andReturn('EUR');

    $currencyConverterService = app(CurrencyConverterService::class);
    expect($currencyConverterService->convert($amount))->toBe($convertAmount)
        ->and($currencyConverterService->convert($amount, true))->toBe('€90.00');
});

it('returns original amount', function (): void {
    $amount = 100.0;

    $currencyConverterService = app(CurrencyConverterService::class);
    expect($currencyConverterService->convert($amount))->toBe($amount);
});
