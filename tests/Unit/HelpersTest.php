<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit;

use Denprog\Meridian\Contracts\CurrencyConverterContract;
use Denprog\Meridian\Contracts\GeoLocationServiceContract;
use Denprog\Meridian\Database\Factories\CountryFactory;
use Denprog\Meridian\Database\Factories\CurrencyFactory;
use Denprog\Meridian\DataTransferObjects\LocationData;
use Denprog\Meridian\Exceptions\GeoIpLookupException;
use Denprog\Meridian\Models\Country;
use Denprog\Meridian\Models\Currency;
use Denprog\Meridian\Services\CountryService;
use Denprog\Meridian\Services\CurrencyService;
use Mockery;

describe('currency helper', function (): void {
    it('returns CurrencyService instance when no code is provided', function (): void {
        $result = currency();
        expect($result)->toBeInstanceOf(CurrencyService::class);
    });

    it('returns Currency model when a valid code is provided', function (): void {
        CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);

        $result = currency('EUR');
        expect($result)->toBeInstanceOf(Currency::class)
            ->and($result->code)->toBe('EUR');
    });

    it('returns null when an invalid code is provided', function (): void {
        $result = currency('XYZ');
        expect($result)->toBeNull();
    });
});

describe('country helper', function (): void {
    it('returns CountryService instance when no code is provided', function (): void {
        $result = country();
        expect($result)->toBeInstanceOf(CountryService::class);
    });

    it('returns Country model when a valid ISO code is provided', function (): void {
        CurrencyFactory::new()->create(['code' => 'EUR', 'enabled' => true]);
        CountryFactory::new()->create(['iso_alpha_2' => 'DE', 'currency_code' => 'EUR']);

        $result = country('DE');
        expect($result)->toBeInstanceOf(Country::class)
            ->and($result->iso_alpha_2)->toBe('DE');
    });

    it('returns null when an invalid ISO code is provided', function (): void {
        // Ensure no country with code 'XX' exists
        $result = country('XX');
        expect($result)->toBeNull();
    });
});

describe('exchangeRate helper', function (): void {
    it('converts zero amount instead of returning service', function (): void {
        $converterMock = Mockery::mock(CurrencyConverterContract::class);
        $converterMock->shouldReceive('convert')->once()->with(0, false)->andReturn(0.0);

        app()->instance(CurrencyConverterContract::class, $converterMock);

        $result = exchangeRate(0);

        expect($result)->toBe(0.0);
    });

    it('returns converter service when amount is not provided', function (): void {
        $converterMock = Mockery::mock(CurrencyConverterContract::class);

        app()->instance(CurrencyConverterContract::class, $converterMock);

        $result = exchangeRate();

        expect($result)->toBe($converterMock);
    });
});

describe('geoLocation helper', function (): void {
    it('returns empty location data when lookup throws exception', function (): void {
        $ipAddress = '8.8.8.8';
        $geoLocationMock = Mockery::mock(GeoLocationServiceContract::class);
        $geoLocationMock
            ->shouldReceive('lookup')
            ->once()
            ->with($ipAddress)
            ->andThrow(new GeoIpLookupException('Lookup failed', $ipAddress));

        app()->instance(GeoLocationServiceContract::class, $geoLocationMock);

        $result = geoLocation($ipAddress);

        expect($result)->toBeInstanceOf(LocationData::class)
            ->and($result->ipAddress)->toBe($ipAddress)
            ->and($result->isEmpty())->toBeTrue();
    });
});
