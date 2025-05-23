<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Denprog\Meridian\Database\Factories\CurrencyFactory;
use Denprog\Meridian\Database\Factories\ExchangeRateFactory;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Models\ExchangeRate;
use Illuminate\Support\Carbon;


test('exchange rate model has correct fillable attributes', function (): void {
    $exchangeRate = new ExchangeRate();
    expect($exchangeRate->getFillable())->toBe([
        'base_currency_code',
        'target_currency_code',
        'rate',
        'rate_date',
    ]);
});

test('exchange rate model has no updated_at timestamp', function (): void {
    expect(ExchangeRate::UPDATED_AT)->toBeNull();
    $exchangeRate = ExchangeRateFactory::new()->create();
    expect(array_key_exists('updated_at', $exchangeRate->getAttributes()))->toBeFalse();
});

test('exchange rate model casts attributes correctly', function (): void {
    $dateString = '2024-05-23';
    $exchangeRate = ExchangeRateFactory::new()->create([
        'rate' => 1.234567,
        'rate_date' => $dateString,
    ]);

    $exchangeRate->refresh();

    expect($exchangeRate->rate)->toBeNumeric();
    $freshExchangeRate = ExchangeRate::find($exchangeRate->id);

    expect($freshExchangeRate->rate)->toBe('1.234567')
        ->and($freshExchangeRate->rate_date)->toBeInstanceOf(Carbon::class)
        ->and($freshExchangeRate->rate_date->toDateString())->toBe($dateString)
        ->and($freshExchangeRate->created_at)->toBeInstanceOf(Carbon::class);

});

test('exchange rate has a base currency', function (): void {
    $baseCurrency = CurrencyFactory::new()->create(['code' => 'USD']);
    $exchangeRate = ExchangeRateFactory::new()->create(['base_currency_code' => $baseCurrency->code]);

    expect($exchangeRate->baseCurrency)->toBeInstanceOf(Currency::class)
        ->and($exchangeRate->baseCurrency->code)->toBe('USD');
});

test('exchange rate base currency can be null if code does not exist', function (): void {
    $exchangeRate = ExchangeRateFactory::new()->create(['base_currency_code' => 'XYZ']);

    expect($exchangeRate->baseCurrency)->toBeNull();
});

test('exchange rate has a target currency', function (): void {
    $targetCurrency = CurrencyFactory::new()->create(['code' => 'EUR']);
    $exchangeRate = ExchangeRateFactory::new()->create(['target_currency_code' => $targetCurrency->code]);

    expect($exchangeRate->targetCurrency)->toBeInstanceOf(Currency::class)
        ->and($exchangeRate->targetCurrency->code)->toBe('EUR');
});

test('exchange rate target currency can be null if code does not exist', function (): void {
    $exchangeRate = ExchangeRateFactory::new()->create(['target_currency_code' => 'ABC']);

    expect($exchangeRate->targetCurrency)->toBeNull();
});

test('exchange rate model uses the correct factory', function (): void {
    expect(ExchangeRate::factory())->toBeInstanceOf(ExchangeRateFactory::class);
});
