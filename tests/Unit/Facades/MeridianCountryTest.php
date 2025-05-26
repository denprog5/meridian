<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Facades;

use Denprog\Meridian\Facades\MeridianCountry;
use Denprog\Meridian\Models\Country;
use Denprog\Meridian\Services\CountryService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

test('facade get() return Country from session', function (): void {
    Config::set('meridian.default_country_iso_code', 'US');
    Country::factory()->create(['iso_alpha_2' => 'US']);
    Session::shouldReceive('get')->with(CountryService::SESSION_KEY_USER_COUNTRY)->andReturn('US');

    $result = MeridianCountry::get();

    expect($result)->toBeInstanceOf(Country::class)
        ->and($result->iso_alpha_2)->toBe('US');
});
