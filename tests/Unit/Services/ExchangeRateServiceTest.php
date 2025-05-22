<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Services;

use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Services\ExchangeRateService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Config::set('meridian.base_currency_code', 'USD');
    Config::set('meridian.exchange_rate_providers.frankfurter.api_url', 'https://api.frankfurter.app');

    if (! Currency::query()->where('code', 'USD')->exists()) {
        Currency::factory()->create(['code' => 'USD']);
    }
    if (! Currency::query()->where('code', 'EUR')->exists()) {
        Currency::factory()->create(['code' => 'EUR']);
    }
    if (! Currency::query()->where('code', 'GBP')->exists()) {
        Currency::factory()->create(['code' => 'GBP']);
    }
});

describe('ExchangeRateService - fetchAndStoreRatesFromFrankfurter', function (): void {
    it('successfully fetches and stores exchange rates for base currency', function (): void {
        Http::fake([
            'api.frankfurter.app/*' => Http::response([
                'amount' => 1.0,
                'base' => 'USD',
                'date' => date('Y-m-d'),
                'rates' => [
                    'EUR' => 0.88331,
                    'GBP' => 0.74605,
                ],
            ]),
        ]);

        $service = app(ExchangeRateService::class);
        $result = $service->fetchAndStoreRatesFromFrankfurter();

        expect($result['success'])->toBeTrue()
            ->and($result['message'])->toContain('Exchange rates fetched and stored successfully.')
            ->and($result['rates_processed'])->toBe(2)
            ->and($result['base_currency'])->toBe('USD');

        $this->assertDatabaseHas('exchange_rates', [
            'base_currency_code' => 'USD',
            'target_currency_code' => 'EUR',
            'rate' => 0.88331,
            'rate_date' => date('Y-m-d'),
        ]);
        $this->assertDatabaseHas('exchange_rates', [
            'base_currency_code' => 'USD',
            'target_currency_code' => 'GBP',
            'rate' => 0.74605,
            'rate_date' => date('Y-m-d'),
        ]);
    });

    it('handles API failure gracefully', function (): void {
        Http::fake([
            'api.frankfurter.app/*' => Http::response(null, 500),
        ]);

        $service = app(ExchangeRateService::class);
        $result = $service->fetchAndStoreRatesFromFrankfurter();

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('Failed to fetch rates from Frankfurter.app');
        $this->assertDatabaseCount('exchange_rates', 0);
    });

    // Add more tests for custom base currency, specific date, target currencies etc.
});

// TODO: Add describe blocks for convertAmount and getAvailableTargetCurrencies
