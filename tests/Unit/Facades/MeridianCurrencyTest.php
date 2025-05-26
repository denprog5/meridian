<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Facades;

use Denprog\Meridian\Facades\MeridianCurrency;
use Denprog\Meridian\Models\Currency;
use Illuminate\Support\Facades\Config;

beforeEach(function (): void {
    Config::set('meridian.base_currency_code', 'USD');
    Config::set('meridian.active_currency_codes', ['USD', 'EUR', 'GBP']);
    Config::set('meridian.display_currency_session_key', 'meridian.user_display_currency');

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

it('facade all() proxies to CurrencyService::all()', function (): void {
    $currency = MeridianCurrency::get();

    expect($currency->code)->toBe('USD');
});
