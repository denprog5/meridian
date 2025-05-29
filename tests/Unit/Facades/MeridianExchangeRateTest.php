<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Facades;

use Denprog\Meridian\Contracts\CurrencyConverterContract;
use Denprog\Meridian\Facades\MeridianExchangeRate;
use Illuminate\Support\Facades\Config;
use Mockery;

beforeEach(function (): void {
    Config::set('meridian.base_currency_code', 'USD');
    Config::set('meridian.cache_duration_days.exchange_rates', 7);

    $this->currencyConverterMock = Mockery::mock(CurrencyConverterContract::class);
    $this->app->instance(CurrencyConverterContract::class, $this->currencyConverterMock);
});

it('facade convert proxies to CurrencyConverterContract method', function (): void {
    $amount = 100.0;
    $expectedConvertedAmount = 85.0;

    $this->currencyConverterMock
        ->shouldReceive('convert')
        ->once()
        ->withArgs([$amount])
        ->andReturn($expectedConvertedAmount);

    $result = MeridianExchangeRate::convert($amount);

    expect($result)->toBe($expectedConvertedAmount);
});
