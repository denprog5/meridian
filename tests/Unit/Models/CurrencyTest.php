<?php

declare(strict_types=1);

use Denprog\Meridian\Models\Country;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Models\ExchangeRate;

test('currency model has correct fillable attributes', function (): void {
    $country = Currency::factory()->create();

    expect(array_keys($country->toArray()))
        ->toBe([
            'name',
            'code',
            'symbol',
            'decimal_places',
            'enabled',
            'updated_at',
            'created_at',
            'id',
        ]);
});

test('currency have countries', function (): void {
    Currency::factory()->create(['code' => 'EUR']);
    Country::factory()->count(3)->create(['currency_code' => 'EUR']);

    $firstCountry = Country::query()->with('currency')->first();
    expect(Country::query()->count())->toBe(3)
        ->and($firstCountry->currency)->toBeInstanceOf(Currency::class)
        ->and($firstCountry->currency->code)->toBe('EUR');
});

test('currency does not have countries', function (): void {
    $currency = Currency::factory()->create(['code' => 'EUR']);
    Country::factory()->count(3)->create(['currency_code' => 'USD']);

    $currency->refresh();

    expect($currency->countries()->count())->toBe(0);
});

test('currency have rates as base', function (): void {
    $currency = Currency::factory()->create(['code' => 'EUR']);
    ExchangeRate::factory()->count(3)->create(['base_currency_code' => 'EUR', 'target_currency_code' => 'USD']);
    ExchangeRate::factory()->count(3)->create(['base_currency_code' => 'USD', 'target_currency_code' => 'EUR']);

    $currency->refresh();

    $latestRateAsBase = Currency::query()->with('latestRateAsBase')->first();
    $latestRateAsTarget = Currency::query()->with('latestRateAsTarget')->first();

    expect($currency->ratesAsBase()->count())->toBe(3)
        ->and($currency->ratesAsTarget()->count())->toBe(3)
        ->and($latestRateAsBase->latestRateAsBase->base_currency_code)->toBe($currency->code)
        ->and($latestRateAsTarget->latestRateAsTarget->target_currency_code)->toBe($currency->code);
});
