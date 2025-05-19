<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Services;

use Denprog\Meridian\Database\Factories\CountryFactory;
use Denprog\Meridian\Models\Country;
use Denprog\Meridian\Services\CountryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;

test('getAllCountries returns all countries', function (): void {
    CountryFactory::new()->count(5)->create();
    $countries = (new CountryService)->getAllCountries(false);
    expect($countries)->toBeInstanceOf(Collection::class)
        ->and($countries)->toHaveCount(5);
});

test('getAllCountries uses cache', function (): void {
    CountryFactory::new()->count(3)->create();

    Cache::shouldReceive('remember')
        ->once()
        ->with('countries.all', Mockery::any(), Mockery::on(function ($closure): true {
            $data = $closure();
            expect($data)->toBeInstanceOf(Collection::class)->and($data)->toHaveCount(3);

            return true;
        }))
        ->andReturn(Country::query()->orderBy('name')->get());

    expect((new CountryService)->getAllCountries())
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(3);
});

test('findCountryByIsoAlpha2 returns correct country', function (): void {
    $countryService = new CountryService;
    $country = CountryFactory::new()->create(['iso_alpha_2' => 'XY']);

    $foundCountry = $countryService->findCountryByIsoAlpha2('XY', false);
    expect($foundCountry)->toBeInstanceOf(Country::class)
        ->and($foundCountry->id)->toBe($country->id);

    $notFoundCountry = $countryService->findCountryByIsoAlpha2('ZZ', false);
    expect($notFoundCountry)->toBeNull();
});

test('findCountryByIsoAlpha2 uses cache', function (): void {
    $countryService = new CountryService;
    CountryFactory::new()->create(['iso_alpha_2' => 'AB']);
    $isoAlpha2 = 'AB';

    Cache::shouldReceive('remember')
        ->once()
        ->with('country.iso_alpha_2.'.mb_strtoupper($isoAlpha2), Mockery::any(), Mockery::on(function ($closure) use ($isoAlpha2): true {
            $data = $closure();
            expect($data)->toBeInstanceOf(Country::class)->and($data->iso_alpha_2)->toBe(mb_strtoupper($isoAlpha2));

            return true;
        }))
        ->andReturn(Country::query()->where('iso_alpha_2', mb_strtoupper($isoAlpha2))->first());

    $foundCountry = $countryService->findCountryByIsoAlpha2($isoAlpha2);
    expect($foundCountry)->toBeInstanceOf(Country::class)->and($foundCountry->iso_alpha_2)->toBe($isoAlpha2);

    $isoAlpha2NotFound = 'CD';
    Cache::shouldReceive('remember')
        ->once()
        ->with('country.iso_alpha_2.'.mb_strtoupper($isoAlpha2NotFound), Mockery::any(), Mockery::any())
        ->andReturnNull();

    $notFound = $countryService->findCountryByIsoAlpha2($isoAlpha2NotFound);
    expect($notFound)->toBeNull();
});

test('findCountryById returns correct country', function (): void {
    $countryService = new CountryService;
    $country = CountryFactory::new()->create();
    $foundCountry = $countryService->findCountryById($country->id, false);
    expect($foundCountry)->toBeInstanceOf(Country::class)
        ->and($foundCountry->id)->toBe($country->id);

    $notFoundCountry = $countryService->findCountryById(999, false);
    expect($notFoundCountry)->toBeNull();
});

test('findCountryById uses cache', function (): void {
    $countryService = new CountryService;
    $country = CountryFactory::new()->create();
    $countryId = $country->id;

    Cache::shouldReceive('remember')
        ->once()
        ->with('country.id.'.$countryId, Mockery::any(), Mockery::on(function ($closure) use ($countryId): true {
            $data = $closure();
            expect($data)->toBeInstanceOf(Country::class)->and($data->id)->toBe($countryId);

            return true;
        }))
        ->andReturn(Country::query()->find($countryId));

    $foundCountry = $countryService->findCountryById($countryId);
    expect($foundCountry)->toBeInstanceOf(Country::class)->and($foundCountry->id)->toBe($countryId);

    $countryIdNotFound = 12345;
    Cache::shouldReceive('remember')
        ->once()
        ->with('country.id.'.$countryIdNotFound, Mockery::any(), Mockery::any())
        ->andReturnNull();

    $notFound = $countryService->findCountryById($countryIdNotFound);
    expect($notFound)->toBeNull();
});
