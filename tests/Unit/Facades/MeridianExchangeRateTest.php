<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Facades;

use Denprog\Meridian\Contracts\CurrencyConverterContract;
use Denprog\Meridian\Facades\MeridianExchangeRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Mockery;

beforeEach(function (): void {
    Config::set('meridian.base_currency_code', 'USD');
    Config::set('meridian.cache_duration_days.exchange_rates', 7);

    $this->currencyConverterMock = Mockery::mock(CurrencyConverterContract::class);
    $this->app->instance(CurrencyConverterContract::class, $this->currencyConverterMock);
});

it('facade convert proxies to CurrencyConverterContract method', function (): void {
    $fromCurrency = 'USD';
    $toCurrency = 'EUR';
    $amount = 100.0;
    $date = Carbon::now();
    $expectedConvertedAmount = 85.0;

    $this->currencyConverterMock
        ->shouldReceive('convert')
        ->once()
        ->withArgs(fn (float $argAmount, string $argFrom, string $argTo, $argDate): bool => abs($argAmount - $amount) < 0.00001 &&
               $argFrom === $fromCurrency &&
               $argTo === $toCurrency &&
               ($argDate instanceof Carbon && $argDate->isSameDay($date)))
        ->andReturn($expectedConvertedAmount);

    $result = MeridianExchangeRate::convert($amount, $fromCurrency, $toCurrency, $date);

    expect($result)->toBe($expectedConvertedAmount);
});
