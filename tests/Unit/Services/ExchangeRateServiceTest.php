<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Denprog\Meridian\Contracts\ExchangeRateServiceContract;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Models\ExchangeRate;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    Config::set('meridian.base_currency_code', 'USD');
    Config::set('meridian.exchange_rate_providers.frankfurter.api_url', 'https://api.frankfurter.app');
});

describe('ExchangeRateService - Rate Management', function (): void {
    beforeEach(function (): void {
        if (! Currency::query()->where('code', 'USD')->exists()) {
            Currency::factory()->create(['code' => 'USD', 'symbol' => '$', 'enabled' => true]);
        }
        if (! Currency::query()->where('code', 'EUR')->exists()) {
            Currency::factory()->create(['code' => 'EUR', 'symbol' => '€', 'enabled' => true]);
        }
        if (! Currency::query()->where('code', 'GBP')->exists()) {
            Currency::factory()->create(['code' => 'GBP', 'symbol' => '£', 'enabled' => true]);
        }
    });

    it('updates exchange rates from provider', function (): void {
        $this->service = app(ExchangeRateServiceContract::class);

        $result = $this->service->fetchAndStoreRatesFromProvider(null, ['EUR', 'GBP']);
        expect(count($result))->toBe(2);

        // Assert rates were updated correctly
        $rates = ExchangeRate::all();
        expect($rates->count())->toBe(2);

        $eurRate = $rates->where('base_currency_code', 'USD')->where('target_currency_code', 'EUR')->first();
        expect($eurRate)->not->toBeNull();

        $gbpRate = $rates->where('base_currency_code', 'USD')->where('target_currency_code', 'GBP')->first();
        expect($gbpRate)->not->toBeNull();
    });
});
