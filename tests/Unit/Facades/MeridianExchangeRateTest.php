<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Facades;

use Denprog\Meridian\Contracts\ExchangeRateProvider;
use Denprog\Meridian\Facades\MeridianExchangeRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Mockery;

beforeEach(function (): void {
    Config::set('meridian.base_currency_code', 'USD');
    Config::set('meridian.cache_duration_days.exchange_rates', 7);
});

it('facade fetchAndStoreRatesFromProvider proxies to ExchangeRateService method', function (): void {
    $baseCurrency = 'USD';
    $targetCurrencies = ['EUR', 'GBP'];
    $expectedDate = Carbon::now();
    $expectedRates = ['EUR' => 0.85, 'GBP' => 0.75];

    $exchangeRateProviderMock = $this->mock(ExchangeRateProvider::class);
    $exchangeRateProviderMock
        ->shouldReceive('getRates')
        ->once()
        ->with(
            $baseCurrency,
            $targetCurrencies,
            Mockery::on(fn ($argument): bool => $argument instanceof Carbon && $argument->isSameDay($expectedDate))
        )
        ->andReturn($expectedRates);

    $result = MeridianExchangeRate::fetchAndStoreRatesFromProvider($baseCurrency, $targetCurrencies, $expectedDate);

    expect($result)->toBe($expectedRates);
});
