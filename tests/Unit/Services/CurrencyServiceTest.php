<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Services;

use Denprog\Meridian\Database\Factories\CurrencyFactory;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Services\CurrencyService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;

afterEach(function (): void {
    Mockery::close();
});

test('getAllCurrencies returns all currencies', function (): void {
    CurrencyFactory::new()->count(5)->create();
    $service = new CurrencyService();
    $currencies = $service->getAllCurrencies(false);
    expect($currencies)->toBeInstanceOf(Collection::class)
        ->and($currencies)->toHaveCount(5);
});

test('getAllCurrencies uses cache', function (): void {
    CurrencyFactory::new()->count(3)->create();
    $service = new CurrencyService();

    Cache::shouldReceive('remember')
        ->once()
        ->with('currencies.all', Mockery::any(), Mockery::on(function ($closure): bool {
            $data = $closure();
            expect($data)->toBeInstanceOf(Collection::class)->and($data)->toHaveCount(3);

            return true;
        }))
        ->andReturn(Currency::query()->orderBy('name')->get());

    expect($service->getAllCurrencies())
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(3);
});

test('findCurrencyById returns correct currency', function (): void {
    $service = new CurrencyService();
    $currency = CurrencyFactory::new()->create();

    $foundCurrency = $service->findCurrencyById($currency->id, false);
    expect($foundCurrency)->toBeInstanceOf(Currency::class)
        ->and($foundCurrency->id)->toBe($currency->id);

    $notFoundCurrency = $service->findCurrencyById(99999, false);
    expect($notFoundCurrency)->toBeNull();
});

test('findCurrencyById uses cache', function (): void {
    $service = new CurrencyService();
    $currency = CurrencyFactory::new()->create();
    $currencyId = $currency->id;

    Cache::shouldReceive('remember')
        ->once()
        ->with('currency.id.'.$currencyId, Mockery::any(), Mockery::on(function ($closure) use ($currencyId): bool {
            $data = $closure();
            expect($data)->toBeInstanceOf(Currency::class)->and($data->id)->toBe($currencyId);

            return true;
        }))
        ->andReturn(Currency::query()->find($currencyId));

    $foundCurrency = $service->findCurrencyById($currencyId);
    expect($foundCurrency)->toBeInstanceOf(Currency::class)->and($foundCurrency->id)->toBe($currencyId);

    $currencyIdNotFound = 12345;
    Cache::shouldReceive('remember')
        ->once()
        ->with('currency.id.'.$currencyIdNotFound, Mockery::any(), Mockery::any())
        ->andReturnNull();

    $notFound = $service->findCurrencyById($currencyIdNotFound);
    expect($notFound)->toBeNull();
});

test('findCurrencyByIsoAlphaCode returns correct currency', function (): void {
    $service = new CurrencyService();
    $currency = CurrencyFactory::new()->create(['iso_alpha_code' => 'XYZ']);

    $foundCurrency = $service->findCurrencyByIsoAlphaCode('XYZ', false);
    expect($foundCurrency)->toBeInstanceOf(Currency::class)
        ->and($foundCurrency->iso_alpha_code)->toBe('XYZ');

    $notFoundCurrency = $service->findCurrencyByIsoAlphaCode('ABC', false);
    expect($notFoundCurrency)->toBeNull();
});

test('findCurrencyByIsoAlphaCode uses cache', function (): void {
    $service = new CurrencyService();
    $isoAlphaCode = 'TST';
    CurrencyFactory::new()->create(['iso_alpha_code' => $isoAlphaCode]);

    Cache::shouldReceive('remember')
        ->once()
        ->with('currency.iso_alpha_code.'.$isoAlphaCode, Mockery::any(), Mockery::on(function ($closure) use ($isoAlphaCode): bool {
            $data = $closure();
            expect($data)->toBeInstanceOf(Currency::class)->and($data->iso_alpha_code)->toBe($isoAlphaCode);

            return true;
        }))
        ->andReturn(Currency::query()->where('iso_alpha_code', $isoAlphaCode)->first());

    $foundCurrency = $service->findCurrencyByIsoAlphaCode($isoAlphaCode);
    expect($foundCurrency)->toBeInstanceOf(Currency::class)->and($foundCurrency->iso_alpha_code)->toBe($isoAlphaCode);

    $isoAlphaCodeNotFound = 'NFD';
    Cache::shouldReceive('remember')
        ->once()
        ->with('currency.iso_alpha_code.'.$isoAlphaCodeNotFound, Mockery::any(), Mockery::any())
        ->andReturnNull();

    $notFound = $service->findCurrencyByIsoAlphaCode($isoAlphaCodeNotFound);
    expect($notFound)->toBeNull();
});

test('findCurrencyByIsoNumericCode returns correct currency', function (): void {
    $service = new CurrencyService();
    $currency = CurrencyFactory::new()->create(['iso_numeric_code' => '999']);

    $foundCurrency = $service->findCurrencyByIsoNumericCode('999', false);
    expect($foundCurrency)->toBeInstanceOf(Currency::class)
        ->and($foundCurrency->iso_numeric_code)->toBe('999');

    $notFoundCurrency = $service->findCurrencyByIsoNumericCode('000', false);
    expect($notFoundCurrency)->toBeNull();
});

test('findCurrencyByIsoNumericCode uses cache', function (): void {
    $service = new CurrencyService();
    $isoNumericCode = '888';
    CurrencyFactory::new()->create(['iso_numeric_code' => $isoNumericCode]);

    Cache::shouldReceive('remember')
        ->once()
        ->with('currency.iso_numeric_code.'.$isoNumericCode, Mockery::any(), Mockery::on(function ($closure) use ($isoNumericCode): bool {
            $data = $closure();
            expect($data)->toBeInstanceOf(Currency::class)->and($data->iso_numeric_code)->toBe($isoNumericCode);

            return true;
        }))
        ->andReturn(Currency::query()->where('iso_numeric_code', $isoNumericCode)->first());

    $foundCurrency = $service->findCurrencyByIsoNumericCode($isoNumericCode);
    expect($foundCurrency)->toBeInstanceOf(Currency::class)->and($foundCurrency->iso_numeric_code)->toBe($isoNumericCode);

    $isoNumericCodeNotFound = '111';
    Cache::shouldReceive('remember')
        ->once()
        ->with('currency.iso_numeric_code.'.$isoNumericCodeNotFound, Mockery::any(), Mockery::any())
        ->andReturnNull();

    $notFound = $service->findCurrencyByIsoNumericCode($isoNumericCodeNotFound);
    expect($notFound)->toBeNull();
});
