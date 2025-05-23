<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Services;

use Denprog\Meridian\Database\Factories\CurrencyFactory;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Services\CurrencyService;
use Denprog\Meridian\Exceptions\BaseCurrencyNotDefinedException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
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

it('list returns currencies based on configured active codes and only enabled ones', function (): void {
    Config::set('meridian.active_currencies', ['EUR', 'JPY', 'USD']);
    CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'JPY', 'enabled' => false]);
    CurrencyFactory::new()->create(['code' => 'USD', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'GBP', 'enabled' => true]);

    $service = new CurrencyService();
    $activeCurrencies = $service->list(); // No cache

    expect($activeCurrencies)->toBeInstanceOf(Collection::class)
        ->and($activeCurrencies)->toHaveCount(2)
        ->and($activeCurrencies->pluck('code')->all())->toBe(['EUR', 'USD']);
});

it('list returns default active currencies if config is empty and only enabled ones', function (): void {
    Config::set('meridian.active_currencies', []);

    CurrencyFactory::new()->create(['code' => 'AUD', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'CAD', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => false]);
    CurrencyFactory::new()->create(['code' => 'XXX', 'enabled' => true]);

    $service = new CurrencyService();
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
        ->with('meridian.active_currencies_collection', Mockery::any(), Mockery::on(function ($closure) use ($expectedCollection): bool {
            $data = $closure();
            expect($data->sortBy('code')->pluck('code')->all())->toEqual($expectedCollection->sortBy('code')->pluck('code')->all());
            return true;
        }))
        ->andReturn($expectedCollection);

    $service = new CurrencyService();
    $activeCurrencies = $service->list();

    expect($activeCurrencies)->toEqual($expectedCollection);
});

it('baseCurrency returns currency from config when valid and enabled', function (): void {
    Config::set('meridian.base_currency_code', 'EUR');
    CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);
    CurrencyFactory::new()->create(['code' => 'USD', 'enabled' => true]);

    $service = new CurrencyService();
    $base = $service->baseCurrency();

    expect($base)->toBeInstanceOf(Currency::class)
        ->and($base->code)->toBe('EUR');
});
