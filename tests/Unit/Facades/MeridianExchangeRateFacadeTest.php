<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Facades;

use Denprog\Meridian\Facades\MeridianExchangeRate;
use Denprog\Meridian\Services\ExchangeRateService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Mockery;

beforeEach(function (): void {
    // Mock the ExchangeRateService and bind it to the container for each test
    $this->exchangeRateServiceMock = $this->mock(ExchangeRateService::class);
    Config::set('meridian.base_currency_code', 'USD');
    Config::set('meridian.cache_duration_days.exchange_rates', 7);
});

afterEach(function (): void {
    Mockery::close();
});

it('facade fetchAndStoreRatesFromProvider proxies to ExchangeRateService method', function (): void {
    $baseCurrency = 'USD';
    $targetCurrencies = ['EUR', 'GBP'];
    $date = Carbon::now();
    $expectedRates = ['EUR' => 0.85, 'GBP' => 0.75];

    $this->exchangeRateServiceMock
        ->shouldReceive('fetchAndStoreRatesFromProvider')
        ->once()
        ->with($baseCurrency, $targetCurrencies, $date)
        ->andReturn($expectedRates);

    $result = MeridianExchangeRate::fetchAndStoreRatesFromProvider($baseCurrency, $targetCurrencies, $date);

    expect($result)->toBe($expectedRates);
});
//
// it('facade convertAmount proxies to ExchangeRateService method', function () {
//    $amount = 100.0;
//    $fromCurrency = 'USD';
//    $toCurrency = 'EUR';
//    $date = Carbon::now();
//    $expectedConvertedAmount = 85.0;
//
//    $this->exchangeRateServiceMock
//        ->shouldReceive('convertAmount')
//        ->once()
//        ->with($amount, $fromCurrency, $toCurrency, $date)
//        ->andReturn($expectedConvertedAmount);
//
//    $result = MeridianExchangeRate::convertAmount($amount, $fromCurrency, $toCurrency, $date);
//
//    expect($result)->toBe($expectedConvertedAmount);
// });
//
// it('facade getAvailableTargetCurrencies proxies to ExchangeRateService method', function () {
//    $baseCurrencyCode = 'USD';
//    $date = Carbon::now();
//    $expectedCurrencies = ['EUR', 'GBP', 'JPY'];
//
//    $this->exchangeRateServiceMock
//        ->shouldReceive('getAvailableTargetCurrencies')
//        ->once()
//        ->with($baseCurrencyCode, $date)
//        ->andReturn($expectedCurrencies);
//
//    $result = MeridianExchangeRate::getAvailableTargetCurrencies($baseCurrencyCode, $date);
//
//    expect($result)->toBe($expectedCurrencies);
// });
//
// it('facade fetchAndStoreRatesFromProvider with nulls proxies correctly', function () {
//    $expectedRates = ['EUR' => 0.85];
//
//    $this->exchangeRateServiceMock
//        ->shouldReceive('fetchAndStoreRatesFromProvider')
//        ->once()
//        ->with(null, null, null) // Service method has defaults for these
//        ->andReturn($expectedRates);
//
//    // Call with no args to test default proxying
//    $result = MeridianExchangeRate::fetchAndStoreRatesFromProvider();
//
//    expect($result)->toBe($expectedRates);
// });
//
// it('facade convertAmount with null date proxies correctly', function () {
//    $amount = 100.0;
//    $fromCurrency = 'USD';
//    $toCurrency = 'EUR';
//    $expectedConvertedAmount = 85.0;
//
//    $this->exchangeRateServiceMock
//        ->shouldReceive('convertAmount')
//        ->once()
//        ->with($amount, $fromCurrency, $toCurrency, null) // Service method has default for date
//        ->andReturn($expectedConvertedAmount);
//
//    // Call with no date to test default proxying for date
//    $result = MeridianExchangeRate::convertAmount($amount, $fromCurrency, $toCurrency);
//
//    expect($result)->toBe($expectedConvertedAmount);
// });
//
// it('facade getAvailableTargetCurrencies with null date proxies correctly', function () {
//    $baseCurrencyCode = 'USD';
//    $expectedCurrencies = ['EUR', 'GBP'];
//
//    $this->exchangeRateServiceMock
//        ->shouldReceive('getAvailableTargetCurrencies')
//        ->once()
//        ->with($baseCurrencyCode, null) // Service method has default for date
//        ->andReturn($expectedCurrencies);
//
//    // Call with no date to test default proxying for date
//    $result = MeridianExchangeRate::getAvailableTargetCurrencies($baseCurrencyCode);
//
//    expect($result)->toBe($expectedCurrencies);
// });
