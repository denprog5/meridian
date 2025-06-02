<?php

namespace Denprog\Meridian\Tests\Unit\Services\Drivers\GeoIP {

use Denprog\Meridian\Contracts\GeoIpDriverContract;
use Denprog\Meridian\DataTransferObjects\LocationData;
use Denprog\Meridian\Exceptions\ConfigurationException;
use Denprog\Meridian\Exceptions\GeoIpDatabaseException;
use Denprog\Meridian\Exceptions\GeoIpLookupException;
use Denprog\Meridian\Exceptions\InvalidIpAddressException;
use Denprog\Meridian\Services\Drivers\GeoIP\MaxMindDatabaseDriver;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use GeoIp2\Exception\InvalidDatabaseException as GeoIpInvalidDatabaseException;

use GeoIp2\Model\City as GeoIp2City;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use \Mockery;
use \Mockery\MockInterface;
use \ReflectionClass;

const TEST_DB_PATH_CONFIG_KEY = 'meridian.geolocation.drivers.maxmind_database.database_path';
const VALID_IP = '81.2.69.142'; // Example public IP
const INVALID_IP = 'not-an-ip';
const UNROUTABLE_IP = '10.0.0.1'; // Example private/unroutable IP

/** @var MockInterface&ConfigRepository */
$configMock;

/** @var MockInterface&Reader */
$mockReader;

/** @var MaxMindDatabaseDriver */
$driver;

/** @var MockInterface&Reader */
$genericReaderMock;

beforeEach(function () {
    $this->configMock = Mockery::mock(ConfigRepository::class);

    // Default config mock for database path
    $this->configMock->shouldReceive('get')
        ->with(TEST_DB_PATH_CONFIG_KEY)
        ->andReturn('geoip/GeoLite2-City.mmdb') // A dummy path
        ->byDefault(); // Allows specific tests to override this expectation easily


    // We will often need to mock the Reader constructor or its methods.
    // For now, we initialize the driver, but specific tests might need to re-initialize
    // with a more controlled Reader mock.
    $this->genericReaderMock = Mockery::mock(Reader::class);
    $this->driver = new MaxMindDatabaseDriver($this->configMock, $this->genericReaderMock);
});

describe('MaxMindDatabaseDriver Construction', function () {
    test('constructor throws ConfigurationException if database_path is not configured', function () {
        $this->configMock->expects('get')
            ->with(TEST_DB_PATH_CONFIG_KEY)
            ->once()
            ->andReturnNull();
        new MaxMindDatabaseDriver($this->configMock, $this->genericReaderMock);
    })->throws(ConfigurationException::class, 'MaxMind database path is not configured.');

    test('constructor throws ConfigurationException if database_path is empty', function () {
        $this->configMock->expects('get')
            ->with(TEST_DB_PATH_CONFIG_KEY)
            ->once()
            ->andReturn('');
        new MaxMindDatabaseDriver($this->configMock, $this->genericReaderMock);
    })->throws(ConfigurationException::class, 'MaxMind database path is not configured.');

    test('constructor resolves database path correctly relative to storage_path', function () {
        // Re-initialize with a fresh config mock for this specific assertion
        $config = Mockery::mock(ConfigRepository::class);
        $config->shouldReceive('get')
            ->with(TEST_DB_PATH_CONFIG_KEY)
            ->once()
            ->andReturn('geoip/custom.mmdb');

        $localReaderMock = Mockery::mock(Reader::class);
        $driver = new MaxMindDatabaseDriver($config, $localReaderMock);

        // Use reflection to check the private $databasePath property
        $reflection = new ReflectionClass($driver);
        $property = $reflection->getProperty('databasePath');
        $property->setAccessible(true);
        $resolvedPathByDriver = $property->getValue($driver);

        // Construct the expected path using the actual storage_path() function
        // This makes the test verify the driver's concatenation logic.
        $relativePathFromConfig = 'geoip/custom.mmdb'; // This matches the 'andReturn' above
        $expectedPath = storage_path('app/' . $relativePathFromConfig);

        // Normalize directory separators for comparison
        $normalize = fn($path) => str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        expect($normalize($resolvedPathByDriver))->toBe($normalize($expectedPath));
    });
});

describe('MaxMindDatabaseDriver - getIdentifier()', function () {
    test('getIdentifier returns correct driver identifier', function () {
        $driver = new MaxMindDatabaseDriver($this->configMock, $this->genericReaderMock);
        expect($driver->getIdentifier())->toBe('maxmind_database');
    });
});

// Further tests for lookup method will be added here.
// We'll need to mock file_exists, is_readable, and the GeoIp2\Database\Reader

describe('MaxMindDatabaseDriver - Lookup Method Input Validation and DB File Checks', function () {
    beforeEach(function () {
        // Reset mocks for global functions for each test in this describe block
        // This requires careful management if tests run in parallel or have complex state.
        // For simplicity here, we assume sequential execution or that Pest handles it.
        global $mockFileExists, $mockIsReadable;
        $mockFileExists = null;
        $mockIsReadable = null;

        // Ensure a driver instance is typically available
        // Specific tests can override $this->configMock as needed before instantiation
        // Use the genericReaderMock from the global beforeEach
        $this->driver = new MaxMindDatabaseDriver($this->configMock, $this->genericReaderMock);
    });

    test('lookup throws InvalidIpAddressException for an invalid IP address', function () {
        $this->driver->lookup(INVALID_IP);
    })->throws(InvalidIpAddressException::class, 'Invalid IP address: ' . INVALID_IP);

    test('lookup throws GeoIpDatabaseException if database file does not exist', function () {
        global $mockFileExists;
        $mockFileExists = false; // file_exists will return false

        // We need to ensure is_readable is not called if file_exists is false, or mock it too.
        // The original code is: if (!file_exists(...) || !is_readable(...))
        // So if file_exists is false, is_readable won't be called due to short-circuiting.

        $this->driver->lookup(VALID_IP);
    })->throws(GeoIpDatabaseException::class, 'GeoIP database file not found or not readable at path:');

    test('lookup throws GeoIpDatabaseException if database file is not readable', function () {
        global $mockFileExists, $mockIsReadable;
        $mockFileExists = true;    // file_exists will return true
        $mockIsReadable = false; // is_readable will return false

        $this->driver->lookup(VALID_IP);
    })->throws(GeoIpDatabaseException::class, 'GeoIP database file not found or not readable at path:');

});


describe('MaxMindDatabaseDriver - Lookup Method with GeoIP2 Reader Interactions', function () {
    beforeEach(function () {
        global $mockFileExists, $mockIsReadable;
        $mockFileExists = true; // Assume DB file exists and is readable for these tests
        $mockIsReadable = true;

        // The driver for this group is initialized later, after $this->mockReader is set up.

        // This is the mock for the GeoIp2\Model\City object returned by the reader
        $this->mockGeoIpCityRecord = Mockery::mock(GeoIp2City::class);

        // Create stdClass objects to represent the nested record data
        $countryData = new \stdClass();
        $countryData->isoCode = 'US';
        $countryData->name = 'United States';
        $countryData->isInEuropeanUnion = false;

        $cityData = new \stdClass();
        $cityData->name = 'Mountain View';

        $postalData = new \stdClass();
        $postalData->code = '94043';

        $locationData = new \stdClass();
        $locationData->latitude = 37.422;
        $locationData->longitude = -122.084;
        $locationData->timeZone = 'America/Los_Angeles';
        $locationData->accuracyRadius = 10;

        // Configure the main mockGeoIpCityRecord to return these stdClass objects by mocking public property access
        $this->mockGeoIpCityRecord->shouldReceive('country')->andReturn($countryData);
        $this->mockGeoIpCityRecord->shouldReceive('city')->andReturn($cityData);
        $this->mockGeoIpCityRecord->shouldReceive('postal')->andReturn($postalData);
        $this->mockGeoIpCityRecord->shouldReceive('location')->andReturn($locationData);

        // Mock jsonSerialize method
        $this->mockGeoIpCityRecord->shouldReceive('jsonSerialize')->andReturn([
            'country' => ['iso_code' => 'US', 'names' => ['en' => 'United States'], 'is_in_european_union' => false],
            'city' => ['names' => ['en' => 'Mountain View']],
            'postal' => ['code' => '94043'],
            'location' => ['latitude' => 37.422, 'longitude' => -122.084, 'time_zone' => 'America/Los_Angeles', 'accuracy_radius' => 10],
        ]);

        // This is the mock for the GeoIp2\Database\Reader class itself
        $this->mockReader = Mockery::mock(Reader::class);
        // Initialize driver with the specific mockReader for this test group
        $this->driver = new MaxMindDatabaseDriver($this->configMock, $this->mockReader);
    });

    afterEach(function () {
        Mockery::close(); // Clean up Mockery state, especially for 'overload' mocks
    });

    test('lookup returns LocationData on successful IP lookup', function () {
        $this->mockReader->shouldReceive('city')->with(VALID_IP)->once()->andReturn($this->mockGeoIpCityRecord);

        $locationData = $this->driver->lookup(VALID_IP);

        expect($locationData)->toBeInstanceOf(LocationData::class)
            ->and($locationData->ipAddress)->toBe(VALID_IP)
            ->and($locationData->countryCode)->toBe('US')
            ->and($locationData->cityName)->toBe('Mountain View')
            ->and($locationData->isEmpty())->toBeFalse();
    });

    test('lookup returns empty LocationData when IP is not found in database', function () {
        $this->mockReader->shouldReceive('city')->with(VALID_IP)->once()->andThrow(new AddressNotFoundException('IP not found.'));

        $locationData = $this->driver->lookup(VALID_IP);

        expect($locationData)->toBeInstanceOf(LocationData::class)
            ->and($locationData->ipAddress)->toBe(VALID_IP)
            ->and($locationData->isEmpty())->toBeTrue();
    });

    test('lookup throws GeoIpDatabaseException if Reader throws InvalidDatabaseException', function () {
        $this->mockReader->shouldReceive('city')->with(VALID_IP)->once()->andThrow(new GeoIpInvalidDatabaseException('Invalid DB format.'));

        $this->driver->lookup(VALID_IP);
    })->throws(GeoIpDatabaseException::class, 'Invalid GeoIP database: Invalid DB format.');

    test('lookup throws ConfigurationException if Reader throws InvalidArgumentException', function () {
        // GeoIP2\Exception\InvalidArgumentException, not the generic PHP one
        $this->mockReader->shouldReceive('city')->with(VALID_IP)->once()->andThrow(new \InvalidArgumentException('Invalid reader argument.'));

        $this->driver->lookup(VALID_IP);
    })->throws(ConfigurationException::class, 'GeoIP Reader configuration error: Invalid reader argument.')
          ->expect(fn (ConfigurationException $e) => expect($e->getPrevious())->toBeInstanceOf(\InvalidArgumentException::class));

    test('lookup throws GeoIpLookupException for other generic Reader exceptions', function () {
        $this->mockReader->shouldReceive('city')->with(VALID_IP)->once()->andThrow(new \RuntimeException('Generic reader error.')); // Using a generic RuntimeException

        $this->driver->lookup(VALID_IP);
    })->throws(GeoIpLookupException::class, 'GeoIP lookup failed: Generic reader error.');

});

} // End of Denprog\Meridian\Tests\Unit\Services\Drivers\GeoIP namespace

// Namespace for mocking global functions used by MaxMindDatabaseDriver
// This namespace MUST match the namespace of the MaxMindDatabaseDriver class itself.
namespace Denprog\Meridian\Services\Drivers\GeoIP {

    // It's tricky to share $mockFileExists and $mockIsReadable across namespaces directly
    // with `global` if they are set in a different namespace's scope (like a test method).
    // A common approach for this style of global function mocking is to use static properties
    // on a helper class within the test namespace, or use a more advanced technique.

    // For this iteration, we'll rely on PHP's `global` keyword behavior. The variables
    // $mockFileExists and $mockIsReadable are declared global within the test methods
    // (in Denprog\Meridian\Tests\Unit\Services\Drivers\GeoIP namespace) and also here.

    function file_exists(string $filename): bool
    {
        global $mockFileExists;
        // Check if the variable was set by a test
        if (isset($GLOBALS['mockFileExists'])) { // Accessing true global scope
            // Optionally, add logic here to check if $filename is the one we intend to mock
            // e.g., if ($filename === storage_path('app/geoip/GeoLite2-City.mmdb')) { ... }
            return $GLOBALS['mockFileExists'];
        }
        return \file_exists($filename); // Call actual global function
    }

    function is_readable(string $filename): bool
    {
        global $mockIsReadable;
        if (isset($GLOBALS['mockIsReadable'])) {
            return $GLOBALS['mockIsReadable'];
        }
        return \is_readable($filename); // Call actual global function
    }
}
