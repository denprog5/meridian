<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Services;

use Denprog\Meridian\Database\Factories\CountryFactory;
use Denprog\Meridian\Enums\Continent;
use Denprog\Meridian\Models\Country;
use Denprog\Meridian\Services\CountryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Mockery;

it('return all countries', function (): void {
    CountryFactory::new()->count(5)->create();
    $countries = (new CountryService)->getAllCountries(false);
    expect($countries)->toBeInstanceOf(Collection::class)
        ->and($countries)->toHaveCount(5);
});

it('return all countries uses cache', function (): void {
    CountryFactory::new()->count(3)->create();

    Cache::shouldReceive('remember')
        ->once()
        ->with('countries.all', Mockery::any(), Mockery::on(function ($closure): bool {
            $data = $closure();
            expect($data)->toBeInstanceOf(Collection::class)->and($data)->toHaveCount(3);

            return true;
        }))
        ->andReturn(Country::query()->orderBy('name')->get());

    expect((new CountryService)->getAllCountries())
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(3);
});

it('return country by ISO alpha 2 code', function (): void {
    $countryService = new CountryService;
    $country = CountryFactory::new()->create(['iso_alpha_2' => 'XY']);

    $foundCountry = $countryService->findByIsoAlpha2Code('XY', false);
    expect($foundCountry)->toBeInstanceOf(Country::class)
        ->and($foundCountry->id)->toBe($country->id);

    $notFoundCountry = $countryService->findByIsoAlpha2Code('ZZ', false);
    expect($notFoundCountry)->toBeNull();
});

it('return country by ISO alpha 2 code uses cache', function (): void {
    $countryService = new CountryService;
    CountryFactory::new()->create(['iso_alpha_2' => 'AB']);
    $isoAlpha2Code = 'AB';

    Cache::shouldReceive('remember')
        ->once()
        ->with('country.iso_alpha_2.'.mb_strtoupper($isoAlpha2Code), Mockery::any(), Mockery::on(function ($closure) use ($isoAlpha2Code): bool {
            $data = $closure();
            expect($data)->toBeInstanceOf(Country::class)
                ->and($data->iso_alpha_2)->toBe(mb_strtoupper($isoAlpha2Code));

            return true;
        }))
        ->andReturn(Country::query()->where('iso_alpha_2', mb_strtoupper($isoAlpha2Code))->first());

    $foundCountry = $countryService->findByIsoAlpha2Code($isoAlpha2Code);
    expect($foundCountry)->toBeInstanceOf(Country::class)->and($foundCountry->iso_alpha_2)->toBe($isoAlpha2Code);

    $isoAlpha2NotFound = 'CD';
    Cache::shouldReceive('remember')
        ->once()
        ->with('country.iso_alpha_2.'.mb_strtoupper($isoAlpha2NotFound), Mockery::any(), Mockery::any())
        ->andReturnNull();

    expect($countryService->findByIsoAlpha2Code($isoAlpha2NotFound))->toBeNull();
});

it('return country by ISO alpha 3 code', function (): void {
    $countryService = new CountryService;
    $country = CountryFactory::new()->create(['iso_alpha_3' => 'XYZ']);

    $foundCountry = $countryService->findCountryByIsoAlpha3Code('XYZ', false);
    expect($foundCountry)->toBeInstanceOf(Country::class)
        ->and($foundCountry->id)->toBe($country->id);

    $notFoundCountry = $countryService->findCountryByIsoAlpha3Code('ZZZ', false);
    expect($notFoundCountry)->toBeNull();
});

it('return country by ISO alpha 3 code uses cache', function (): void {
    $countryService = new CountryService;
    CountryFactory::new()->create(['iso_alpha_3' => 'ABC']);
    $isoAlpha3Code = 'ABC';

    Cache::shouldReceive('remember')
        ->once()
        ->with('country.iso_alpha_3.'.mb_strtoupper($isoAlpha3Code), Mockery::any(), Mockery::on(function ($closure) use ($isoAlpha3Code): bool {
            $data = $closure();
            expect($data)->toBeInstanceOf(Country::class)->and($data->iso_alpha_3)->toBe(mb_strtoupper($isoAlpha3Code));

            return true;
        }))
        ->andReturn(Country::query()->where('iso_alpha_3', mb_strtoupper($isoAlpha3Code))->first());

    $foundCountry = $countryService->findCountryByIsoAlpha3Code($isoAlpha3Code);
    expect($foundCountry)->toBeInstanceOf(Country::class)->and($foundCountry->iso_alpha_3)->toBe($isoAlpha3Code);

    $isoAlpha3NotFound = 'DEF';
    Cache::shouldReceive('remember')
        ->once()
        ->with('country.iso_alpha_3.'.mb_strtoupper($isoAlpha3NotFound), Mockery::any(), Mockery::any())
        ->andReturnNull();

    $notFound = $countryService->findCountryByIsoAlpha3Code($isoAlpha3NotFound);
    expect($notFound)->toBeNull();
});

it('return correct countries by continent', function (): void {
    $countryService = new CountryService;

    CountryFactory::new()->forContinent(Continent::EUROPE)->count(3)->create();
    CountryFactory::new()->forContinent(Continent::ASIA)->count(2)->create();

    $europeCountries = $countryService->getCountriesByContinent(Continent::EUROPE, false);
    expect($europeCountries)->toBeInstanceOf(Collection::class)
        ->and($europeCountries)->toHaveCount(3)
        ->and($europeCountries->every(fn (Country $country): bool => $country->continent_code === Continent::EUROPE))->toBeTrue();

    $antarcticaCountries = $countryService->getCountriesByContinent(Continent::ANTARCTICA, false);
    expect($antarcticaCountries)->toBeInstanceOf(Collection::class)
        ->and($antarcticaCountries)->toBeEmpty();
});

it('return correct countries by continent uses cache', function (): void {
    $countryService = new CountryService;
    $europeContinent = Continent::EUROPE;
    CountryFactory::new()->forContinent($europeContinent)->count(2)->create();

    Cache::shouldReceive('remember')
        ->once()
        ->with('countries.continent.'.$europeContinent->value, Mockery::any(), Mockery::on(function ($closure) use ($europeContinent): bool {
            $data = $closure();
            expect($data)->toBeInstanceOf(Collection::class)
                ->and($data)->toHaveCount(2)
                ->and($data->every(fn (Country $country): bool => $country->continent_code === $europeContinent))->toBeTrue();

            return true;
        }))
        ->andReturn(Country::query()->where('continent_code', $europeContinent)->orderBy('name')->get());

    $foundCountries = $countryService->getCountriesByContinent($europeContinent);
    expect($foundCountries)->toBeInstanceOf(Collection::class)->toHaveCount(2);

    $asiaContinent = Continent::ASIA;
    Cache::shouldReceive('remember')
        ->once()
        ->with('countries.continent.'.$asiaContinent->value, Mockery::any(), Mockery::any())
        ->andReturn(new Collection()); // Empty collection for a cache miss that results in no countries

    $notFound = $countryService->getCountriesByContinent($asiaContinent);
    expect($notFound)->toBeInstanceOf(Collection::class)->toBeEmpty();
});

it('return correct country by id', function (): void {
    $countryService = new CountryService;
    $country = CountryFactory::new()->create();
    $foundCountry = $countryService->findCountryById($country->id, false);
    expect($foundCountry)->toBeInstanceOf(Country::class)
        ->and($foundCountry->id)->toBe($country->id);

    $notFoundCountry = $countryService->findCountryById(999, false);
    expect($notFoundCountry)->toBeNull();
});

it('return correct country by id uses cache', function (): void {
    $countryService = new CountryService;
    $country = CountryFactory::new()->create();
    $countryId = $country->id;

    Cache::shouldReceive('remember')
        ->once()
        ->with('country.id.'.$countryId, Mockery::any(), Mockery::on(function ($closure) use ($countryId): bool {
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
