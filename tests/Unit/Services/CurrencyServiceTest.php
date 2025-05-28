<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Services;

use Closure;
use Denprog\Meridian\Contracts\CurrencyServiceContract;
use Denprog\Meridian\Database\Factories\CurrencyFactory;
use Denprog\Meridian\Models\Currency;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Mockery;

beforeEach(function (): void {
    Config::set('meridian.base_currency_code', 'USD');
    Config::set('meridian.active_currency_codes', ['USD', 'EUR', 'GBP']);
    Config::set('meridian.display_currency_session_key', 'meridian.user_display_currency');
    Config::set('meridian.cache_duration_days.currencies', 1);
});

it('returns all currencies', function (): void {
    CurrencyFactory::new()->count(5)->create();

    $currencies = app(CurrencyServiceContract::class)->all(false);
    expect($currencies)->toBeInstanceOf(Collection::class)
        ->and($currencies)->toHaveCount(5);
});

it('returns all currencies from cache', function (): void {
    $currencies = CurrencyFactory::new()->count(3)->create();

    Cache::expects('remember')
        ->once()
        ->with('currencies.all', Mockery::any(), Mockery::type(Closure::class))
        ->andReturn($currencies);

    expect(app(CurrencyServiceContract::class)->all())
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(3);
});

it('returns currency by id', function (): void {
    $currencyService = app(CurrencyServiceContract::class);
    $currency = CurrencyFactory::new()->create();

    $foundCurrency = $currencyService->findById($currency->id, false);
    expect($foundCurrency)->toBeInstanceOf(Currency::class)
        ->and($foundCurrency->id)->toBe($currency->id);

    expect($currencyService->findById(99999, false))->toBeNull();
});

it('returns currency by id from cache', function (): void {
    $currencyService = app(CurrencyServiceContract::class);
    $currency = CurrencyFactory::new()->create();
    $currencyId = $currency->id;

    Cache::shouldReceive('remember')
        ->once()
        ->with('currency.id.'.$currencyId, Mockery::any(), Mockery::type(Closure::class))
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
    $currencyService = app(CurrencyServiceContract::class);
    $currency = CurrencyFactory::new()->create(['code' => 'XYZ']);

    $foundCurrency = $currencyService->findByCode('XYZ', false);
    expect($foundCurrency)->toBeInstanceOf(Currency::class)
        ->and($foundCurrency->code)->toBe($currency->code);

    expect($currencyService->findByCode('ABC', false))->toBeNull();
});

it('returns currency by code uses cache', function (): void {
    $currencyService = app(CurrencyServiceContract::class);
    $currencyCode = 'USD';
    CurrencyFactory::new()->create(['code' => $currencyCode, 'enabled' => true]);

    Cache::shouldReceive('remember')
        ->once()
        ->with('currency.code.'.$currencyCode, Mockery::any(), Mockery::type(Closure::class))
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

it('list returns currencies based on configured active codes and only enabled ones', function (): void {
    Config::set('meridian.active_currencies', ['EUR', 'JPY', 'USD']);
    CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'JPY', 'enabled' => false]);
    CurrencyFactory::new()->create(['code' => 'USD', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'GBP', 'enabled' => true]);

    $service = app(CurrencyServiceContract::class);
    $activeCurrencies = $service->list(); // No cache

    expect($activeCurrencies)->toBeInstanceOf(Collection::class)
        ->and($activeCurrencies)->toHaveCount(3)
        ->and($activeCurrencies->pluck('code')->all())->toBe(['EUR', 'GBP', 'USD']);
});

it('list returns default active currencies if config is empty and only enabled ones', function (): void {
    Config::set('meridian.active_currencies', []);

    CurrencyFactory::new()->create(['code' => 'AUD', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'CAD', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => false]);
    CurrencyFactory::new()->create(['code' => 'XXX', 'enabled' => true]);

    $service = app(CurrencyServiceContract::class);
    $activeCurrencies = $service->list();

    expect($activeCurrencies)->toBeInstanceOf(Collection::class)
        ->and($activeCurrencies->pluck('code')->all())->toBe(['AUD', 'CAD'])
        ->and($activeCurrencies)->toHaveCount(2);
});

it('list uses cache for active currencies collection', function (): void {
    Config::set('meridian.active_currencies', ['USD', 'EUR']);
    $usd = CurrencyFactory::new()->create(['code' => 'USD', 'enabled' => true]);
    $eur = CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);

    $expectedCollection = new Collection([$usd, $eur]);

    Cache::shouldReceive('remember')
        ->once()
        ->with('meridian.active_currencies_collection', Mockery::any(), Mockery::type(Closure::class))
        ->andReturn($expectedCollection);

    $service = app(CurrencyServiceContract::class);
    $activeCurrencies = $service->list();

    expect($activeCurrencies)->toEqual($expectedCollection);
});

it('base currency returns currency from config when valid', function (): void {
    Config::set('meridian.base_currency_code', 'EUR');
    CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'USD', 'enabled' => true]);

    $service = app(CurrencyServiceContract::class);
    $base = $service->baseCurrency();

    expect($base)->toBeInstanceOf(Currency::class)
        ->and($base->code)->toBe('EUR');

    $baseReturned = $service->baseCurrency();

    expect($baseReturned)->toBeInstanceOf(Currency::class)
        ->and($baseReturned->code)->toBe('EUR');
});

test('get currency', function (): void {
    CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'USD', 'enabled' => true]);

    $service = app(CurrencyServiceContract::class);
    $currency = $service->get();
    expect($currency)->toBeInstanceOf(Currency::class)
        ->and($currency->code)->toBe('USD');

    $currencyFromReturned = $service->get();
    expect($currencyFromReturned)->toBeInstanceOf(Currency::class)
        ->and($currencyFromReturned->code)->toBe('USD');
});

test('get currency from session when they exist (session hit)', function (): void {
    CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'USD', 'enabled' => true]);

    $sessionKey = CurrencyServiceContract::SESSION_CURRENCY_CODE;

    Session::expects('get')
        ->once()
        ->with($sessionKey)
        ->andReturn('EUR');

    $service = app(CurrencyServiceContract::class);
    $currency = $service->get();
    expect($currency)->toBeInstanceOf(Currency::class)
        ->and($currency->code)->toBe('EUR');
});

test('get currency if not in session (session miss)', function (): void {
    CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'USD', 'enabled' => true]);

    $sessionKey = CurrencyServiceContract::SESSION_CURRENCY_CODE;

    Session::expects('get')
        ->once()
        ->with($sessionKey)
        ->andReturnNull();

    $service = app(CurrencyServiceContract::class);
    $currency = $service->get();
    expect($currency)->toBeInstanceOf(Currency::class)
        ->and($currency->code)->toBe('USD');
});

test('get currency from session', function (): void {
    CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'USD', 'enabled' => true]);

    $sessionKey = CurrencyServiceContract::SESSION_CURRENCY_CODE;
    Session::put($sessionKey, 'EUR');

    $service = app(CurrencyServiceContract::class);
    $currency = $service->get();
    expect($currency)->toBeInstanceOf(Currency::class)
        ->and($currency->code)->toBe('EUR');
});

test('get base currency when session store invalid currency', function (): void {
    CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'USD', 'enabled' => true]);

    $sessionKey = CurrencyServiceContract::SESSION_CURRENCY_CODE;
    Session::put($sessionKey, 'XYZ'); // Invalid currency code

    $service = app(CurrencyServiceContract::class);
    $currency = $service->get();
    expect($currency)->toBeInstanceOf(Currency::class)
        ->and($currency->code)->toBe('USD'); // Should fall back to base
});

test('get base currency when session store disabled currency', function (): void {
    CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => false]);
    CurrencyFactory::new()->create(['code' => 'USD', 'enabled' => true]);

    $sessionKey = CurrencyServiceContract::SESSION_CURRENCY_CODE;
    Session::put($sessionKey, 'EUR');

    $service = app(CurrencyServiceContract::class);
    $currency = $service->get();
    expect($currency)->toBeInstanceOf(Currency::class)
        ->and($currency->code)->toBe('USD'); // Should fall back to base
});

test('set currency', function (): void {
    CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'USD', 'enabled' => true]);

    $service = app(CurrencyServiceContract::class);
    $sessionKey = CurrencyServiceContract::SESSION_CURRENCY_CODE;

    Session::shouldReceive('put')->once()->with($sessionKey, 'EUR');
    $service->set('EUR');

    // Test setting an invalid currency (should default to base)
    Session::shouldReceive('put')->once()->with($sessionKey, 'USD');
    $service->set('XYZ');

    // Test setting a disabled currency (should default to base)
    CurrencyFactory::new()->create(['code' => 'GBP', 'enabled' => false]);
    Session::shouldReceive('put')->once()->with($sessionKey, 'USD');
    $service->set('GBP');
});
