<?php

declare(strict_types=1);

namespace Denprog\Meridian\Tests\Unit\Services\Drivers\GeoIP {

    use Denprog\Meridian\DataTransferObjects\LocationData;
    use Denprog\Meridian\Exceptions\ConfigurationException;
    use Denprog\Meridian\Exceptions\GeoIpDatabaseException;
    use Denprog\Meridian\Exceptions\GeoIpLookupException;
    use Denprog\Meridian\Exceptions\InvalidIpAddressException;
    use Denprog\Meridian\Services\Drivers\GeoIP\MaxMindDatabaseDriver;
    use GeoIp2\Database\Reader;
    use GeoIp2\Exception\AddressNotFoundException;
    use GeoIp2\Model\City as GeoIp2City;
    use Illuminate\Contracts\Config\Repository as ConfigRepository;
    use Mockery;
    use ReflectionClass;

    const TEST_DB_PATH_CONFIG_KEY = 'meridian.geolocation.drivers.maxmind_database.database_path';
    const VALID_IP = '81.2.69.142'; // Example public IP
    const INVALID_IP = 'not-an-ip';
    const UNROUTABLE_IP = '10.0.0.1';

    beforeEach(function (): void {
        $this->configMock = Mockery::mock(ConfigRepository::class);

        // Default config mock for database path
        $this->configMock->shouldReceive('get')
            ->with(TEST_DB_PATH_CONFIG_KEY)
            ->andReturn('geoip/GeoLite2-City.mmdb') // A dummy path
            ->byDefault();

        // We will often need to mock the Reader constructor or its methods.
        // For now, we initialize the driver, but specific tests might need to re-initialize
        // with a more controlled Reader mock.
        $this->genericReaderMock = Mockery::mock(Reader::class);
        $this->driver = new MaxMindDatabaseDriver($this->configMock, $this->genericReaderMock);
    });

    describe('MaxMindDatabaseDriver Construction', function (): void {
        test('constructor throws ConfigurationException if database_path is not configured', function (): void {
            $this->configMock->expects('get')
                ->with(TEST_DB_PATH_CONFIG_KEY)
                ->once()
                ->andReturnNull();
            new MaxMindDatabaseDriver($this->configMock, $this->genericReaderMock);
        })->throws(ConfigurationException::class, 'MaxMind database path is not configured.');

        test('constructor throws ConfigurationException if database_path is empty', function (): void {
            $this->configMock->expects('get')
                ->with(TEST_DB_PATH_CONFIG_KEY)
                ->once()
                ->andReturn('');
            new MaxMindDatabaseDriver($this->configMock, $this->genericReaderMock);
        })->throws(ConfigurationException::class, 'MaxMind database path is not configured.');

        test('constructor resolves database path correctly relative to storage_path', function (): void {
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
            $expectedPath = storage_path('app/'.$relativePathFromConfig);

            // Normalize directory separators for comparison
            $normalize = fn ($path) => str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

            expect($normalize($resolvedPathByDriver))->toBe($normalize($expectedPath));
        });
    });

    describe('MaxMindDatabaseDriver - getIdentifier()', function (): void {
        test('getIdentifier returns correct driver identifier', function (): void {
            $driver = new MaxMindDatabaseDriver($this->configMock, $this->genericReaderMock);
            expect($driver->getIdentifier())->toBe('maxmind_database');
        });
    });

    // Further tests for lookup method will be added here.
    // We'll need to mock file_exists, is_readable, and the GeoIp2\Database\Reader

    describe('MaxMindDatabaseDriver - Lookup Method Input Validation and DB File Checks', function (): void {
        beforeEach(function (): void {
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

        test('lookup throws InvalidIpAddressException for an invalid IP address', function (): void {
            $this->driver->lookup(INVALID_IP);
        })->throws(InvalidIpAddressException::class, 'Invalid IP address: '.INVALID_IP);

        test('lookup throws GeoIpDatabaseException if database file does not exist', function (): void {
            global $mockFileExists;
            $mockFileExists = false; // file_exists will return false

            // We need to ensure is_readable is not called if file_exists is false, or mock it too.
            // The original code is: if (!file_exists(...) || !is_readable(...))
            // So if file_exists is false, is_readable won't be called due to short-circuiting.

            $this->driver->lookup(VALID_IP);
        })->throws(GeoIpDatabaseException::class, 'GeoIP database file not found or not readable at path:');

        test('lookup throws GeoIpDatabaseException if database file is not readable', function (): void {
            global $mockFileExists, $mockIsReadable;
            $mockFileExists = true;    // file_exists will return true
            $mockIsReadable = false; // is_readable will return false

            $this->driver->lookup(VALID_IP);
        })->throws(GeoIpDatabaseException::class, 'GeoIP database file not found or not readable at path:');

    });

    describe('MaxMindDatabaseDriver - Lookup Method with GeoIP2 Reader Interactions', function (): void {
        beforeEach(function (): void {
            global $mockFileExists, $mockIsReadable;
            $mockFileExists = true; // Assume DB file exists and is readable for these tests
            $mockIsReadable = true;

            // The driver for this group is initialized later, after $this->mockReader is set up.

            // --- Attribute Definitions for Records (used to construct raw data for GeoIp2\Model\City) ---
            $countryAttribs = [
                'iso_code' => 'US',
                'names' => ['en' => 'United States'],
                'is_in_european_union' => false,
            ];
            $cityAttribs = [
                'names' => ['en' => 'Mountain View'],
            ];
            $postalAttribs = [
                'code' => '94043',
            ];
            $locationAttribs = [
                'latitude' => 37.422,
                'longitude' => -122.084,
                'time_zone' => 'America/Los_Angeles',
                'accuracy_radius' => 10,
            ];

            // Raw data structure for GeoIp2\Model\City constructor
            $rawCityData = [
                'country' => $countryAttribs,
                'city' => $cityAttribs,
                'postal' => $postalAttribs,
                'location' => $locationAttribs,
                'traits' => ['ip_address' => VALID_IP],
            ];

            // Partial mock for GeoIp2\Model\City, constructed with raw data.
            // Its own constructor will initialize its readonly record properties.
            $this->cityModel = Mockery::mock(GeoIp2City::class, [$rawCityData, ['en']])
                ->makePartial()
                ->shouldAllowMockingProtectedMethods(); // In case protected methods are involved in property access

            // This is the mock for the GeoIp2\Database\Reader class itself
            $this->mockReader = Mockery::mock(Reader::class);
            // Initialize driver with the specific mockReader for this test group
            $this->driver = new MaxMindDatabaseDriver($this->configMock, $this->mockReader);
        });

        afterEach(function (): void {
            Mockery::close(); // Clean up Mockery state, especially for 'overload' mocks
        });

        test('lookup returns LocationData on successful IP lookup', function (): void {
            $this->mockReader->shouldReceive('city')->with(VALID_IP)->once()->andReturn($this->cityModel);

            // DEBUG: Verify properties on the partial cityModel before lookup
            expect($this->cityModel->country)->toBeInstanceOf(\GeoIp2\Record\Country::class);
            expect($this->cityModel->country->isoCode)->toBe('US');
            expect($this->cityModel->city->name)->toBe('Mountain View');

            $locationData = $this->driver->lookup(VALID_IP);

            expect($locationData)->toBeInstanceOf(LocationData::class)
                ->and($locationData->ipAddress)->toBe(VALID_IP)
                ->and($locationData->countryCode)->toBe('US')
                ->and($locationData->cityName)->toBe('Mountain View')
                ->and($locationData->isEmpty())->toBeFalse();
        });

        test('lookup returns empty LocationData when IP is not found in database', function (): void {
            $this->mockReader->shouldReceive('city')->with(VALID_IP)->once()->andThrow(new AddressNotFoundException('IP not found.'));

            $locationData = $this->driver->lookup(VALID_IP);

            expect($locationData)->toBeInstanceOf(LocationData::class)
                ->and($locationData->ipAddress)->toBe(VALID_IP)
                ->and($locationData->isEmpty())->toBeTrue();
        });

        test('lookup throws GeoIpDatabaseException if Reader throws InvalidDatabaseException', function (): void {
            $this->mockReader->shouldReceive('city')->with(VALID_IP)->once()->andThrow(new \MaxMind\Db\Reader\InvalidDatabaseException('Simulated DB error.'));

            $this->driver->lookup(VALID_IP);
        })->throws(GeoIpDatabaseException::class, 'Invalid GeoIP database: Simulated DB error.');

        test('lookup throws ConfigurationException if Reader throws InvalidArgumentException', function (): void {
            // This mockReader is specific to the 'Lookup Method with GeoIP2 Reader Interactions' describe block
            // and is re-initialized in its beforeEach.
            $this->mockReader->shouldReceive('city')->with(VALID_IP)->once()->andThrow(new \InvalidArgumentException('Invalid reader argument.'));

            $expectedExceptionClass = ConfigurationException::class;
            $expectedMessage = 'GeoIP Reader configuration error: Invalid reader argument.';
            $expectedPreviousExceptionClass = \InvalidArgumentException::class;

            try {
                $this->driver->lookup(VALID_IP);
                $this->fail("Expected exception '{$expectedExceptionClass}' was not thrown.");
            } catch (ConfigurationException $e) {
                expect($e)->toBeInstanceOf($expectedExceptionClass);
                expect($e->getMessage())->toBe($expectedMessage);
                expect($e->getPrevious())->toBeInstanceOf($expectedPreviousExceptionClass);
                // If all assertions pass, the test effectively passes.
            } catch (\Throwable $e) {
                // Caught an unexpected exception type
                $this->fail(sprintf(
                    "Expected exception '%s' but caught '%s' with message: '%s'",
                    $expectedExceptionClass,
                    $e::class,
                    $e->getMessage()
                ));
            }
        });

        test('lookup throws GeoIpLookupException for other generic Reader exceptions', function (): void {
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

        return $GLOBALS['mockFileExists'] ?? \file_exists($filename); // Call actual global function
    }

    function is_readable(string $filename): bool
    {
        global $mockIsReadable;

        return $GLOBALS['mockIsReadable'] ?? \is_readable($filename); // Call actual global function
    }
}
