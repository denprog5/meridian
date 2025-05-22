<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Services;

use Denprog\Meridian\Database\Factories\CurrencyFactory;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Services\CurrencyService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mockery;

beforeEach(function (): void {
    Config::set('meridian.base_currency_code', 'USD');
    Config::set('meridian.active_currency_codes', ['USD', 'EUR', 'GBP']);
    Config::set('meridian.display_currency_session_key', 'meridian.user_display_currency');
    Config::set('meridian.cache_duration_days.currencies', 1);
});

it('returns all currencies', function (): void {
    CurrencyFactory::new()->count(5)->create();

    $currencies = (new CurrencyService())->all(false);
    expect($currencies)->toBeInstanceOf(Collection::class)
        ->and($currencies)->toHaveCount(5);
});

it('returns all currencies from cache', function (): void {
    CurrencyFactory::new()->count(3)->create();

    Cache::shouldReceive('remember')
        ->once()
        ->with('currencies.all', Mockery::any(), Mockery::on(function ($closure): bool {
            $data = $closure();
            expect($data)->toBeInstanceOf(Collection::class)->and($data)->toHaveCount(3);

            return true;
        }))
        ->andReturn(Currency::query()->orderBy('name')->get());

    expect((new CurrencyService())->all())
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(3);
});

it('returns currency by id', function (): void {
    $currencyService = new CurrencyService();
    $currency = CurrencyFactory::new()->create();

    $foundCurrency = $currencyService->findById($currency->id, false);
    expect($foundCurrency)->toBeInstanceOf(Currency::class)
        ->and($foundCurrency->id)->toBe($currency->id);

    expect($currencyService->findById(99999, false))->toBeNull();
});

it('returns currency by id from cache', function (): void {
    $currencyService = new CurrencyService();
    $currency = CurrencyFactory::new()->create();
    $currencyId = $currency->id;

    Cache::shouldReceive('remember')
        ->once()
        ->with('currency.id.'.$currencyId, Mockery::any(), Mockery::on(function ($closure) use ($currencyId): bool {
            $data = $closure();
            expect($data)->toBeInstanceOf(Currency::class)
                ->and($data->id)->toBe($currencyId);

            return true;
        }))
        ->andReturn(Currency::query()->find($currencyId));

    $foundCurrency = $currencyService->findById($currencyId);
    expect($foundCurrency)->toBeInstanceOf(Currency::class)
        ->and($foundCurrency->id)->toBe($currencyId);

    $currencyIdNotFound = 12345;
    Cache::shouldReceive('remember')
        ->once()
        ->with('currency.id.'.$currencyIdNotFound, Mockery::any(), Mockery::any())
        ->andReturnNull();

    expect($currencyService->findById($currencyIdNotFound))->toBeNull();
});

it('returns currency by code', function (): void {
    $currencyService = new CurrencyService();
    $currency = CurrencyFactory::new()->create(['code' => 'XYZ']);

    $foundCurrency = $currencyService->findByCode('XYZ', false);
    expect($foundCurrency)->toBeInstanceOf(Currency::class)
        ->and($foundCurrency->code)->toBe($currency->code);

    expect($currencyService->findByCode('ABC', false))->toBeNull();
});

it('returns currency by code uses cache', function (): void {
    $currencyService = new CurrencyService();
    $currencyCode = 'USD';
    CurrencyFactory::new()->create(['code' => $currencyCode, 'enabled' => true]);

    Cache::shouldReceive('remember')
        ->once()
        ->with('currency.code.'.$currencyCode, Mockery::any(), Mockery::on(function ($closure) use ($currencyCode): bool {
            $data = $closure();
            expect($data)->toBeInstanceOf(Currency::class)->and($data->code)->toBe($currencyCode);

            return true;
        }))
        ->andReturn(Currency::query()->where('code', $currencyCode)->first());

    $foundCurrency = $currencyService->findByCode($currencyCode);
    expect($foundCurrency)->toBeInstanceOf(Currency::class)
        ->and($foundCurrency->code)->toBe($currencyCode);

    $currencyCodeNotFound = 'NFD';
    Cache::shouldReceive('remember')
        ->once()
        ->with('currency.code.'.$currencyCodeNotFound, Mockery::any(), Mockery::any())
        ->andReturnNull();

    expect($currencyService->findByCode($currencyCodeNotFound))->toBeNull();
});
