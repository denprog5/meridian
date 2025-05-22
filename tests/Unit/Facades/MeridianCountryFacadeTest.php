<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Facades;

use Denprog\Meridian\Enums\Continent;
use Denprog\Meridian\Facades\MeridianCountry;
use Denprog\Meridian\Models\Country;
use Denprog\Meridian\Services\CountryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

beforeEach(function (): void {
    Config::set('meridian.default_country_iso_code', 'US');
});

test('facade get() return Country from session', function (): void {
    Country::factory()->create(['iso_alpha_2' => 'US']);
    Session::shouldReceive('get')->with(CountryService::SESSION_KEY_USER_COUNTRY)->andReturn('US');

    $result = MeridianCountry::get();

    expect($result)->toBeInstanceOf(Country::class)
        ->and($result->iso_alpha_2)->toBe('US');
});
//
// it('facade setUserCountry proxies to CountryService::setUserCountry', function () {
//    $this->countryServiceMock->shouldReceive('setUserCountry')->once()->with('CA')->andReturn(true);
//
//    $result = MeridianCountry::setUserCountry('CA');
//
//    expect($result)->toBeTrue();
// });
//
// it('facade getDefaultCountry proxies to CountryService::getDefaultCountry', function () {
//    $mockCountry = Country::factory()->make(['iso_alpha_2' => 'GB']);
//    $this->countryServiceMock->shouldReceive('getDefaultCountry')->once()->andReturn($mockCountry);
//
//    $result = MeridianCountry::getDefaultCountry();
//
//    expect($result)->toBeInstanceOf(Country::class)
//        ->and($result->iso_alpha_2)->toBe('GB');
// });
//
// it('facade all() proxies to CountryService::getAllCountries()', function () {
//    $mockCountryCollection = new Collection([Country::factory()->make(['name' => 'Testland'])]);
//    $this->countryServiceMock->shouldReceive('getAllCountries')->once()->with(true, 60)->andReturn($mockCountryCollection);
//
//    $result = MeridianCountry::all();
//
//    expect($result)->toBeInstanceOf(Collection::class)
//        ->and($result->first()->name)->toBe('Testland');
// });
//
// it('facade findByIsoAlpha2Code proxies to CountryService::findByIsoAlpha2Code', function () {
//    $mockCountry = Country::factory()->make(['iso_alpha_2' => 'DE']);
//    $this->countryServiceMock->shouldReceive('findByIsoAlpha2Code')->once()->with('DE', true, 60)->andReturn($mockCountry);
//
//    $result = MeridianCountry::findByIsoAlpha2Code('DE');
//
//    expect($result)->toBeInstanceOf(Country::class)
//        ->and($result->iso_alpha_2)->toBe('DE');
// });
//
// it('facade findByIsoAlpha3Code proxies to CountryService::findCountryByIsoAlpha3Code', function () {
//    $mockCountry = Country::factory()->make(['iso_alpha_3' => 'FRA']);
//    $this->countryServiceMock->shouldReceive('findCountryByIsoAlpha3Code')->once()->with('FRA', true, 60)->andReturn($mockCountry);
//
//    $result = MeridianCountry::findByIsoAlpha3Code('FRA');
//
//    expect($result)->toBeInstanceOf(Country::class)
//        ->and($result->iso_alpha_3)->toBe('FRA');
// });
//
// it('facade findByContinent proxies to CountryService::getCountriesByContinent', function () {
//    $mockCountryCollection = new Collection([Country::factory()->make(['continent_code' => Continent::EUROPE->value])]);
//    $this->countryServiceMock->shouldReceive('getCountriesByContinent')->once()->with(Continent::EUROPE, true, 60)->andReturn($mockCountryCollection);
//
//    $result = MeridianCountry::findByContinent(Continent::EUROPE);
//
//    expect($result)->toBeInstanceOf(Collection::class)
//        ->and($result->first()->continent_code)->toBe(Continent::EUROPE->value);
// });
