<?php

use Denprog\Meridian\DataTransferObjects\LocationData;
use GeoIp2\Record\City;
use GeoIp2\Record\Continent;
use GeoIp2\Record\Country;
use GeoIp2\Record\Location;
use GeoIp2\Record\MaxMind;
use GeoIp2\Record\Postal;
use GeoIp2\Record\RepresentedCountry;
use GeoIp2\Record\Subdivision;
use GeoIp2\Record\Traits;

// Mock GeoIp2\Model\City for testing fromMaxMindRecord
function mockMaxMindCityRecord(array $data = []): \GeoIp2\Model\City
{
    $cityData = array_merge([
        'city' => ['names' => ['en' => 'Test City']],
        'continent' => ['code' => 'NA', 'names' => ['en' => 'North America']],
        'country' => ['iso_code' => 'US', 'names' => ['en' => 'Test Country'], 'is_in_european_union' => false],
        'location' => [
            'latitude' => 34.05,
            'longitude' => -118.25,
            'accuracy_radius' => 50,
            'time_zone' => 'America/Los_Angeles',
        ],
        'postal' => ['code' => '90210'],
        'represented_country' => ['iso_code' => 'US', 'names' => ['en' => 'Test Represented Country'], 'type' => 'military'],
        'subdivisions' => [['iso_code' => 'CA', 'names' => ['en' => 'Test Subdivision']]],
        'traits' => [
            'ip_address' => '123.123.123.123',
            'network' => '123.123.123.0/24',
            'is_anonymous_proxy' => false,
            'is_satellite_provider' => false,
        ],
    ], $data);

    return new \GeoIp2\Model\City([
        'city' => $cityData['city'],
        'continent' => $cityData['continent'],
        'country' => $cityData['country'],
        'location' => $cityData['location'],
        'maxmind' => [], // Raw data for MaxMind, can be empty array if no specific maxmind data needed for the model
        'postal' => $cityData['postal'],
        'represented_country' => $cityData['represented_country'],
        'registered_country' => $cityData['country'], // Assuming registered_country can be same as country for this test, or provide specific empty array if needed
        'subdivisions' => $cityData['subdivisions'], // This should be an array of raw subdivision data arrays
        'traits' => $cityData['traits'],
    ], ['en']);
}

it('can be created from a MaxMind record', function () {
    $ipAddress = '192.168.1.100';
    $maxMindRecord = mockMaxMindCityRecord();

    $locationData = LocationData::fromMaxMindRecord($maxMindRecord, $ipAddress);

    expect($locationData->ipAddress)->toBe($ipAddress)
        ->and($locationData->countryCode)->toBe('US')
        ->and($locationData->countryName)->toBe('Test Country')
        ->and($locationData->cityName)->toBe('Test City')
        ->and($locationData->postalCode)->toBe('90210')
        ->and($locationData->latitude)->toBe(34.05)
        ->and($locationData->longitude)->toBe(-118.25)
        ->and($locationData->timezone)->toBe('America/Los_Angeles')
        ->and($locationData->accuracyRadius)->toBe(50)
        ->and($locationData->isInEuropeanUnion)->toBeFalse()
        ->and($locationData->raw)->toBe($maxMindRecord->jsonSerialize());
});

it('can be created as an empty DTO', function () {
    $ipAddress = '127.0.0.1';
    $locationData = LocationData::empty($ipAddress);

    expect($locationData->ipAddress)->toBe($ipAddress)
        ->and($locationData->countryCode)->toBeNull()
        ->and($locationData->countryName)->toBeNull()
        ->and($locationData->cityName)->toBeNull()
        ->and($locationData->postalCode)->toBeNull()
        ->and($locationData->latitude)->toBeNull()
        ->and($locationData->longitude)->toBeNull()
        ->and($locationData->timezone)->toBeNull()
        ->and($locationData->accuracyRadius)->toBeNull()
        ->and($locationData->isInEuropeanUnion)->toBeFalse()
        ->and($locationData->raw)->toBeNull();
});

it('correctly identifies if it is empty', function () {
    $ipAddress = '127.0.0.1';
    $emptyLocation = LocationData::empty($ipAddress);
    $filledLocation = LocationData::fromMaxMindRecord(mockMaxMindCityRecord(), $ipAddress);

    expect($emptyLocation->isEmpty())->toBeTrue()
        ->and($filledLocation->isEmpty())->toBeFalse();
});

it('can be converted to an array', function () {
    $ipAddress = '192.168.1.100';
    $maxMindRecord = mockMaxMindCityRecord();
    $locationData = LocationData::fromMaxMindRecord($maxMindRecord, $ipAddress);

    $arrayData = $locationData->toArray();

    expect($arrayData)->toBeArray()
        ->and($arrayData['ipAddress'])->toBe($ipAddress)
        ->and($arrayData['countryCode'])->toBe('US')
        ->and($arrayData['countryName'])->toBe('Test Country')
        ->and($arrayData['isInEuropeanUnion'])->toBeFalse(); // Check a few key fields
});

it('correctly determines if country is in EU', function () {
    $germanyRecord = mockMaxMindCityRecord([
        'country' => ['iso_code' => 'DE', 'names' => ['en' => 'Germany'], 'is_in_european_union' => true],
    ]);
    $usRecord = mockMaxMindCityRecord([
        'country' => ['iso_code' => 'US', 'names' => ['en' => 'United States'], 'is_in_european_union' => false],
    ]);

    $locationDE = LocationData::fromMaxMindRecord($germanyRecord, '1.1.1.1');
    $locationUS = LocationData::fromMaxMindRecord($usRecord, '2.2.2.2');

    expect($locationDE->isInEuropeanUnion)->toBeTrue()
        ->and($locationUS->isInEuropeanUnion)->toBeFalse();
});
