<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Services;

use Denprog\Meridian\Contracts\CurrencyServiceContract;
use Denprog\Meridian\Database\Factories\CountryFactory;
use Denprog\Meridian\Database\Factories\CurrencyFactory;
use Denprog\Meridian\Database\Factories\ExchangeRateFactory;
use Denprog\Meridian\Services\CurrencyConverterService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

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

test('converts between currencies with base currency', function (): void {
    $amount = 100.0;
    $convertAmount = 400.0;

    $currencyConverterService = app(CurrencyConverterService::class);
    expect($currencyConverterService->convertBetween($amount, 'PLN'))->toBe($convertAmount)
        ->and($currencyConverterService->convertBetween(amount: $amount, toCurrencyCode: 'PLN', returnFormatted: true))->toBe('PLN 400.00');
});

test('converts between currencies', function (): void {
    $amount = 100.0;
    $convertAmount = 110.0;

    $currencyConverterService = app(CurrencyConverterService::class);
    expect($currencyConverterService->convertBetween($amount, 'USD', 'EUR'))->toBe($convertAmount)
        ->and($currencyConverterService->convertBetween(amount: $amount, toCurrencyCode: 'USD', fromCurrencyCode: 'EUR', returnFormatted: true, locale: 'de_DE'))->toBe('110,00 $')
        ->and($currencyConverterService->convertBetween($amount, 'USD', 'EUR', true))->toBe('$110.00');
});
