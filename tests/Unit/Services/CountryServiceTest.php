<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Services;

use Denprog\Meridian\Database\Factories\CountryFactory;
use Denprog\Meridian\Database\Factories\CurrencyFactory;
use Denprog\Meridian\Enums\Continent;
use Denprog\Meridian\Models\Country;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Services\CountryService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mockery;

beforeEach(function () {
    Session::spy();
    Log::spy();

    Config::set('meridian.default_country_iso_code', 'US');
});

it('return all countries', function (): void {
    CountryFactory::new()->count(5)->create();
    $countries = (new CountryService)->all(false);
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

    expect((new CountryService)->all())
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

    $foundCountry = $countryService->findByIsoAlpha3Code('XYZ', false);
    expect($foundCountry)->toBeInstanceOf(Country::class)
        ->and($foundCountry->id)->toBe($country->id);

    $notFoundCountry = $countryService->findByIsoAlpha3Code('ZZZ', false);
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

    $foundCountry = $countryService->findByIsoAlpha3Code($isoAlpha3Code);
    expect($foundCountry)->toBeInstanceOf(Country::class)->and($foundCountry->iso_alpha_3)->toBe($isoAlpha3Code);

    $isoAlpha3NotFound = 'DEF';
    Cache::shouldReceive('remember')
        ->once()
        ->with('country.iso_alpha_3.'.mb_strtoupper($isoAlpha3NotFound), Mockery::any(), Mockery::any())
        ->andReturnNull();

    $notFound = $countryService->findByIsoAlpha3Code($isoAlpha3NotFound);
    expect($notFound)->toBeNull();
});

it('return correct countries by continent', function (): void {
    $countryService = new CountryService;

    CountryFactory::new()->forContinent(Continent::EUROPE)->count(3)->create();
    CountryFactory::new()->forContinent(Continent::ASIA)->count(2)->create();

    $europeCountries = $countryService->findByContinent(Continent::EUROPE, false);
    expect($europeCountries)->toBeInstanceOf(Collection::class)
        ->and($europeCountries)->toHaveCount(3)
        ->and($europeCountries->every(fn (Country $country): bool => $country->continent_code === Continent::EUROPE))->toBeTrue();

    $antarcticaCountries = $countryService->findByContinent(Continent::ANTARCTICA, false);
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

    $foundCountries = $countryService->findByContinent($europeContinent);
    expect($foundCountries)->toBeInstanceOf(Collection::class)->toHaveCount(2);

    $asiaContinent = Continent::ASIA;
    Cache::shouldReceive('remember')
        ->once()
        ->with('countries.continent.'.$asiaContinent->value, Mockery::any(), Mockery::any())
        ->andReturn(new Collection()); // Empty collection for a cache miss that results in no countries

    $notFound = $countryService->findByContinent($asiaContinent);
    expect($notFound)->toBeInstanceOf(Collection::class)->toBeEmpty();
});

it('return correct country by id', function (): void {
    $countryService = new CountryService;
    $country = CountryFactory::new()->create();
    $foundCountry = $countryService->findById($country->id, false);
    expect($foundCountry)->toBeInstanceOf(Country::class)
        ->and($foundCountry->id)->toBe($country->id);

    $notFoundCountry = $countryService->findById(999, false);
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

    $foundCountry = $countryService->findById($countryId);
    expect($foundCountry)->toBeInstanceOf(Country::class)->and($foundCountry->id)->toBe($countryId);

    $countryIdNotFound = 12345;
    Cache::shouldReceive('remember')
        ->once()
        ->with('country.id.'.$countryIdNotFound, Mockery::any(), Mockery::any())
        ->andReturnNull();

    $notFound = $countryService->findById($countryIdNotFound);
    expect($notFound)->toBeNull();
});

it('retrieves a country with its currency relationship', function (): void {
    $currency = CurrencyFactory::new()->create();
    $country = CountryFactory::new()->create([
        'currency_code' => $currency->code,
    ]);
    $countryService = new CountryService();

    $foundCountry = $countryService->findByIsoAlpha2Code($country->iso_alpha_2, false);

    // Assert
    expect($foundCountry)->toBeInstanceOf(Country::class)
        ->and($foundCountry->id)->toBe($country->id)
        ->and($foundCountry->currency)->not->toBeNull()
        ->and($foundCountry->currency)->toBeInstanceOf(Currency::class)
        ->and($foundCountry->currency_code)->toBe($currency->code)
        ->and($foundCountry->currency->id)->toBe($currency->id)
        ->and($foundCountry->currency->name)->toBe($currency->name);
});

it('default returns country from config when valid', function (): void {
    $configuredCountryCode = 'DE';
    $country = CountryFactory::new()->create(['iso_alpha_2' => $configuredCountryCode]);
    Config::set('meridian.default_country_iso_code', $configuredCountryCode);

    $service = new CountryService();
    $defaultCountry = $service->default();

    expect($defaultCountry)->toBeInstanceOf(Country::class)
        ->and($defaultCountry->iso_alpha_2)->toBe($configuredCountryCode)
        ->and($defaultCountry->id)->toBe($country->id);
});

it('default returns US country when config code is not found or not set', function (): void {
    Config::set('meridian.default_country_iso_code', 'XX');
    $usCountry = CountryFactory::new()->create(['iso_alpha_2' => 'US']);

    $service = new CountryService();
    $defaultCountry = $service->default();

    expect($defaultCountry)->toBeInstanceOf(Country::class)
        ->and($defaultCountry->iso_alpha_2)->toBe('US')
        ->and($defaultCountry->id)->toBe($usCountry->id);
});

it('default method caches the resolved default country in a property', function (): void {
    $configuredCountryCode = 'FR';
    CountryFactory::new()->create(['iso_alpha_2' => $configuredCountryCode]);
    Config::set('meridian.default_country_iso_code', $configuredCountryCode);

    $service = new CountryService();
    $firstCallCountry = $service->default();
    $secondCallCountry = $service->default();

    expect($secondCallCountry)->toBe($firstCallCountry)
        ->and($secondCallCountry->iso_alpha_2)->toBe($configuredCountryCode);
});

it('set puts valid country iso in session and updates property', function (): void {
    $countryCode = 'DE';
    CountryFactory::new()->create(['iso_alpha_2' => $countryCode]);
    $service = new CountryService();

    $service->set($countryCode);

    Session::shouldHaveReceived('put')->with(CountryService::SESSION_KEY_USER_COUNTRY, $countryCode)->once();

    $resolvedCountry = $service->get();
    expect($resolvedCountry->iso_alpha_2)->toBe($countryCode);
});

it('set logs warning and does not put in session for non-existent country code', function (): void {
    $nonExistentCode = 'XX';
    $service = new CountryService();

    $service->set($nonExistentCode);

    Log::shouldHaveReceived('warning')
        ->withArgs(function (string $message, array $context = []) use ($nonExistentCode) {
            return str_contains($message, 'Attempt to set user country to non-existent or disabled country.') &&
                   isset($context['code']) && $context['code'] === $nonExistentCode;
        })
        ->once();

    Session::shouldNotHaveReceived('put');
});

it('set converts input country code to uppercase before processing', function (): void {
    $lowerCaseCode = 'fr';
    $upperCaseCode = 'FR';
    CountryFactory::new()->create(['iso_alpha_2' => $upperCaseCode]);
    $service = new CountryService();

    $service->set($lowerCaseCode);

    Session::shouldHaveReceived('put')->with(CountryService::SESSION_KEY_USER_COUNTRY, $upperCaseCode)->once();

    $resolvedCountry = $service->get();
    expect($resolvedCountry->iso_alpha_2)->toBe($upperCaseCode);
});

it('get returns country from session when valid iso code in session', function (): void {
    $sessionCountryCode = 'JP';
    $sessionCountry = CountryFactory::new()->create(['iso_alpha_2' => $sessionCountryCode]);
    Session::shouldReceive('get')->with(CountryService::SESSION_KEY_USER_COUNTRY)->once()->andReturn($sessionCountryCode);

    $service = new CountryService();
    $country = $service->get();

    expect($country)->toBeInstanceOf(Country::class)
        ->and($country->id)->toBe($sessionCountry->id)
        ->and($country->iso_alpha_2)->toBe($sessionCountryCode);
});

it('get returns default country when no iso code in session', function (): void {
    Session::shouldReceive('get')->with(CountryService::SESSION_KEY_USER_COUNTRY)->once()->andReturn(null);

    $defaultCountryCode = 'CA';
    $defaultCountry = CountryFactory::new()->create(['iso_alpha_2' => $defaultCountryCode]);
    Config::set('meridian.default_country_iso_code', $defaultCountryCode);

    $service = new CountryService();
    $country = $service->get();

    expect($country)->toBeInstanceOf(Country::class)
        ->and($country->id)->toBe($defaultCountry->id)
        ->and($country->iso_alpha_2)->toBe($defaultCountryCode);
});

it('get returns default country when invalid iso code in session', function (): void {
    Session::shouldReceive('get')->with(CountryService::SESSION_KEY_USER_COUNTRY)->once()->andReturn('XX');

    $defaultCountryCode = 'AU';
    $defaultCountry = CountryFactory::new()->create(['iso_alpha_2' => $defaultCountryCode]);
    Config::set('meridian.default_country_iso_code', $defaultCountryCode);

    $service = new CountryService();
    $country = $service->get();

    expect($country)->toBeInstanceOf(Country::class)
        ->and($country->id)->toBe($defaultCountry->id)
        ->and($country->iso_alpha_2)->toBe($defaultCountryCode);
});

it('get returns default country when country from session is not found in database', function (): void {
    Session::shouldReceive('get')->with(CountryService::SESSION_KEY_USER_COUNTRY)->once()->andReturn('NZ');

    $defaultCountryCode = 'GB';
    $defaultCountry = CountryFactory::new()->create(['iso_alpha_2' => $defaultCountryCode]);
    Config::set('meridian.default_country_iso_code', $defaultCountryCode);

    $service = new CountryService();
    $country = $service->get();

    expect($country)->toBeInstanceOf(Country::class)
        ->and($country->id)->toBe($defaultCountry->id)
        ->and($country->iso_alpha_2)->toBe($defaultCountryCode);
});

it('get method caches the resolved country in a property for subsequent calls', function (): void {
    $sessionCountryCode = 'BR';
    CountryFactory::new()->create(['iso_alpha_2' => $sessionCountryCode]);
    Session::shouldReceive('get')->with(CountryService::SESSION_KEY_USER_COUNTRY)->once()->andReturn($sessionCountryCode);

    $service = new CountryService();
    $firstCallCountry = $service->get();

    $secondCallCountry = $service->get();

    expect($secondCallCountry)->toBe($firstCallCountry)
        ->and($secondCallCountry->iso_alpha_2)->toBe($sessionCountryCode);
});
