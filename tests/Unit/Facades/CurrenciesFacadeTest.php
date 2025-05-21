<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Facades;

use Denprog\Meridian\Facades\Currencies;
use Denprog\Meridian\Models\Currency;
use Illuminate\Database\Eloquent\Collection;
use Mockery;

afterEach(function (): void {
    Mockery::close();
});

test('Currencies facade proxies getAllCurrencies to service', function (): void {
    $expectedCurrencies = new Collection([new Currency(['name' => 'Test Dollar'])]);

    Currencies::shouldReceive('getAllCurrencies')
        ->once()
        ->andReturn($expectedCurrencies);

    $result = Currencies::getAllCurrencies();

    expect($result)->toBe($expectedCurrencies);
});

test('Currencies facade proxies findCurrencyById to service', function (): void {
    $expectedCurrency = new Currency(['id' => 1, 'name' => 'Test Dollar']);

    Currencies::shouldReceive('findCurrencyById')
        ->once()
        ->with(1)
        ->andReturn($expectedCurrency);

    $result = Currencies::findCurrencyById(1);

    expect($result)->toBe($expectedCurrency);
});

test('Currencies facade proxies findCurrencyByIsoAlphaCode to service', function (): void {
    $expectedCurrency = new Currency(['iso_alpha_code' => 'TSD', 'name' => 'Test Dollar']);

    Currencies::shouldReceive('findCurrencyByIsoAlphaCode')
        ->once()
        ->with('TSD')
        ->andReturn($expectedCurrency);

    $result = Currencies::findCurrencyByIsoAlphaCode('TSD');

    expect($result)->toBe($expectedCurrency);
});

test('Currencies facade proxies findCurrencyByIsoNumericCode to service', function (): void {
    $expectedCurrency = new Currency(['iso_numeric_code' => '999', 'name' => 'Test Dollar']);

    Currencies::shouldReceive('findCurrencyByIsoNumericCode')
        ->once()
        ->with('999')
        ->andReturn($expectedCurrency);

    $result = Currencies::findCurrencyByIsoNumericCode('999');

    expect($result)->toBe($expectedCurrency);
});
