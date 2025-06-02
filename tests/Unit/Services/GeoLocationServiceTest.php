<?php

declare(strict_types=1);

use Denprog\Meridian\Contracts\GeoIpDriverContract;
use Denprog\Meridian\DataTransferObjects\LocationData;
use Denprog\Meridian\Services\GeoLocationService;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

$defaultConfig = [
    'meridian.geolocation.session.store' => false,
    'meridian.geolocation.session.key' => 'meridian_location',
];

beforeEach(function (): void {
    Config::set('meridian.geolocation.session.key', 'meridian_location');
    Config::set('meridian.geolocation.driver', 'maxmind_database');

    $this->mockDriver = $this->mock(GeoIpDriverContract::class);
    $this->service = new GeoLocationService($this->mockDriver);
});

test('successful geolocation lookup returns LocationData object', function (): void {
    $ipAddress = '81.2.69.142';
    $expectedLocationData = LocationData::empty($ipAddress);

    $this->mockDriver->shouldReceive('lookup')
        ->once()
        ->with($ipAddress)
        ->andReturn($expectedLocationData);

    $result = $this->service->lookup($ipAddress);

    expect($result)->toBeInstanceOf(LocationData::class)
        ->and($result->ipAddress)->toBe($ipAddress);
});

test('lookup with an invalid IP address returns empty LocationData', function (): void {
    $ipAddress = 'invalid-ip';

    $this->mockDriver->shouldNotReceive('lookup');

    $result = $this->service->lookup($ipAddress);

    expect($result)->toBeInstanceOf(LocationData::class)
        ->and($result->isEmpty())->toBeTrue()
        ->and($result->ipAddress)->toBe($ipAddress);
});

test('lookup when GeoIP driver throws AddressNotFoundException returns empty LocationData', function (): void {
    $ipAddress = '127.0.0.1';

    $this->mockDriver->shouldReceive('lookup')
        ->once()
        ->with($ipAddress)
        ->andThrow(new AddressNotFoundException('Address not found'));

    $result = $this->service->lookup($ipAddress);

    expect($result)->toBeInstanceOf(LocationData::class)
        ->and($result->isEmpty())->toBeTrue()
        ->and($result->ipAddress)->toBe($ipAddress);
});

test('lookup when GeoIP driver throws a generic exception returns empty LocationData', function (): void {
    $ipAddress = '8.8.8.8';

    $this->mockDriver->shouldReceive('lookup')
        ->once()
        ->with($ipAddress)
        ->andThrow(new Exception('Generic driver error'));

    $result = $this->service->lookup($ipAddress);

    expect($result)->toBeInstanceOf(LocationData::class)
        ->and($result->isEmpty())->toBeTrue()
        ->and($result->ipAddress)->toBe($ipAddress);
});

test('retrieves location data from session when available and session enabled', function (): void {
    $ipAddress = '8.8.4.4';
    $locationArray = [
        'ipAddress' => $ipAddress,
        'countryCode' => 'US',
        'countryName' => 'United States',
        'cityName' => 'Mountain View',
        'postalCode' => '94043',
        'latitude' => 37.422,
        'longitude' => -122.084,
        'timezone' => 'America/Los_Angeles',
        'accuracyRadius' => 1000,
        'isInEuropeanUnion' => false,
        'raw' => ['another_raw_key' => 'data'],
    ];

    $sessionKey = config()->string('meridian.geolocation.session.key', 'meridian_location');

    Session::expects('get')->once()
        ->with($sessionKey)
        ->andReturn($locationArray);

    $result = $this->service->getLocationFromSession();

    expect($result)->toBeInstanceOf(LocationData::class)
        ->and($result->ipAddress)->toBe($ipAddress)
        ->and($result->countryCode)->toBe('US')
        ->and($result->toArray())->toBe($locationArray); // Compare array form for simplicity
});
