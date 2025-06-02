<?php

declare(strict_types=1);

use Denprog\Meridian\Contracts\GeoIpDriverContract;
use Denprog\Meridian\DataTransferObjects\LocationData;
use Denprog\Meridian\Services\GeoLocationService;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Session\Store as SessionStore;

// Default config values
$defaultConfig = [
    'meridian.geolocation.session.store' => false,
    'meridian.geolocation.session.key' => 'meridian_location',
];

beforeEach(function () use ($defaultConfig): void {
    $this->mockApp = Mockery::mock(Application::class);
    $this->mockDriver = Mockery::mock(GeoIpDriverContract::class);
    $this->mockConfig = Mockery::mock(ConfigRepository::class);
    $this->mockSession = Mockery::mock(SessionStore::class);

    // Mock app to resolve the driver
    $this->mockApp->shouldReceive('make')
        ->with(GeoIpDriverContract::class)
        ->andReturn($this->mockDriver);

    // Default config expectations
    $this->mockConfig->allows('get')
        ->with('meridian.geolocation.session.store', false)
        ->andReturn($defaultConfig['meridian.geolocation.session.store']);
    $this->mockConfig->allows('get')
        ->with('meridian.geolocation.session.key', 'meridian_location')
        ->andReturn($defaultConfig['meridian.geolocation.session.key']);
    $this->mockConfig->allows('get')
        ->with('meridian.geolocation.driver') // Default driver alias
        ->andReturn('maxmind_database');

    $this->service = new GeoLocationService($this->mockApp, $this->mockConfig, $this->mockSession);
});

afterEach(function (): void {
    Mockery::close();
});

test('successful geolocation lookup returns LocationData object', function (): void {
    // (ID: GEOIP002-SUB018C1)
    $ipAddress = '81.2.69.142'; // Example public IP
    $expectedLocationData = LocationData::empty($ipAddress); // Replace with a more complete mock if needed

    $this->mockDriver->shouldReceive('lookup')
        ->once()
        ->with($ipAddress)
        ->andReturn($expectedLocationData);

    $result = $this->service->lookup($ipAddress);

    expect($result)->toBeInstanceOf(LocationData::class)
        ->and($result->ipAddress)->toBe($ipAddress);
});

test('lookup with an invalid IP address returns empty LocationData', function (): void {
    // (ID: GEOIP002-SUB018C2)
    $ipAddress = 'invalid-ip';

    // Driver should not be called for an invalid IP format before attempting lookup
    // The service might do basic validation, or the driver might throw an error
    // For this test, assume the service handles basic validation or catches driver error for invalid format
    $this->mockDriver->shouldNotReceive('lookup');

    $result = $this->service->lookup($ipAddress);

    expect($result)->toBeInstanceOf(LocationData::class)
        ->and($result->isEmpty())->toBeTrue()
        ->and($result->ipAddress)->toBe($ipAddress);
});

test('lookup when GeoIP driver throws AddressNotFoundException returns empty LocationData', function (): void {
    // (ID: GEOIP002-SUB018C3)
    $ipAddress = '127.0.0.1'; // Example private IP that might not be found

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
    // (ID: GEOIP002-SUB018C4)
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

test('stores location data in session when session is enabled and lookup is successful', function (): void {
    $ipAddress = '8.8.8.8';
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
        'raw' => ['some_raw_data_key' => 'value'],
    ];
    $expectedLocationData = LocationData::fromArray($locationArray);

    // Ensure session is enabled in config for this test
    $this->mockConfig->shouldReceive('get')->with('meridian.geolocation.session.store', false)->andReturn(true);
    // Re-initialize service to pick up new config mock behavior for session.store
    // This requires careful handling of how mocks are setup in beforeEach vs. per-test
    // For simplicity here, we'll assume the beforeEach config is general enough, or we'd need a more complex setup.
    // Let's refine the beforeEach to allow overriding for specific tests if needed, or create a new service instance.

    // For this specific test, let's create a new service instance with modified config
    $configMockForSessionTest = Mockery::mock(ConfigRepository::class);
    $configMockForSessionTest->shouldReceive('get')->with('meridian.geolocation.driver')->andReturn('maxmind_database'); // from original beforeEach
    $configMockForSessionTest->shouldReceive('get')->with('meridian.geolocation.session.key', 'meridian_location')->andReturn('test_session_key');
    $configMockForSessionTest->shouldReceive('get')->with('meridian.geolocation.session.store', false)->andReturn(true); // Explicitly enable session

    $serviceWithSessionEnabled = new GeoLocationService($this->mockApp, $configMockForSessionTest, $this->mockSession);

    $this->mockDriver->shouldReceive('lookup')
        ->once()
        ->with($ipAddress)
        ->andReturn($expectedLocationData);

    $this->mockSession->shouldReceive('put')
        ->once()
        ->with('test_session_key', $expectedLocationData->toArray());

    $serviceWithSessionEnabled->lookup($ipAddress);
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

    // Configure mocks for this specific test
    $configMockForSessionTest = Mockery::mock(ConfigRepository::class);
    $configMockForSessionTest->shouldReceive('get')->with('meridian.geolocation.driver')->andReturn('maxmind_database');
    $configMockForSessionTest->shouldReceive('get')->with('meridian.geolocation.session.key', 'meridian_location')->andReturn('test_session_key_get');
    $configMockForSessionTest->shouldReceive('get')->with('meridian.geolocation.session.store', false)->andReturn(true); // Session enabled

    $sessionMockForGetTest = Mockery::mock(SessionStore::class);
    $sessionMockForGetTest->shouldReceive('get')
        ->once()
        ->with('test_session_key_get')
        ->andReturn($locationArray);

    $serviceWithSessionEnabled = new GeoLocationService($this->mockApp, $configMockForSessionTest, $sessionMockForGetTest);

    $result = $serviceWithSessionEnabled->getLocationFromSession();

    expect($result)->toBeInstanceOf(LocationData::class)
        ->and($result->ipAddress)->toBe($ipAddress)
        ->and($result->countryCode)->toBe('US')
        ->and($result->toArray())->toBe($locationArray); // Compare array form for simplicity
});

test('does not store location data in session when session is disabled', function (): void {
    $ipAddress = '1.1.1.1';
    $locationArray = ['ipAddress' => $ipAddress, 'countryCode' => 'AU'];
    $expectedLocationData = LocationData::fromArray($locationArray);

    // Configure mocks for this specific test
    $configMockForNoSessionTest = Mockery::mock(ConfigRepository::class);
    $configMockForNoSessionTest->shouldReceive('get')->with('meridian.geolocation.driver')->andReturn('maxmind_database');
    $configMockForNoSessionTest->shouldReceive('get')->with('meridian.geolocation.session.key', 'meridian_location')->andReturn('test_session_key_disabled');
    $configMockForNoSessionTest->shouldReceive('get')->with('meridian.geolocation.session.store', false)->andReturn(false); // Explicitly disable session

    // Session mock should not be called for put
    $sessionMockForDisabledTest = Mockery::mock(SessionStore::class);
    $sessionMockForDisabledTest->shouldNotReceive('put');

    $serviceWithSessionDisabled = new GeoLocationService($this->mockApp, $configMockForNoSessionTest, $sessionMockForDisabledTest);

    $this->mockDriver->shouldReceive('lookup')
        ->once()
        ->with($ipAddress)
        ->andReturn($expectedLocationData);

    $serviceWithSessionDisabled->lookup($ipAddress);
});

test('getLocationFromSession returns null when session is disabled', function (): void {
    $configMockForNoSessionTest = Mockery::mock(ConfigRepository::class);
    $configMockForNoSessionTest->shouldReceive('get')->with('meridian.geolocation.driver')->andReturn('maxmind_database');
    $configMockForNoSessionTest->shouldReceive('get')->with('meridian.geolocation.session.key', 'meridian_location')->andReturn('test_session_key_disabled_get');
    $configMockForNoSessionTest->shouldReceive('get')->with('meridian.geolocation.session.store', false)->andReturn(false); // Session disabled

    // Session mock should not be called for get
    $sessionMockForDisabledTest = Mockery::mock(SessionStore::class);
    $sessionMockForDisabledTest->shouldNotReceive('get');

    $serviceWithSessionDisabled = new GeoLocationService($this->mockApp, $configMockForNoSessionTest, $sessionMockForDisabledTest);

    $result = $serviceWithSessionDisabled->getLocationFromSession();
    expect($result)->toBeNull();
});

test('storeLocationInSession does nothing when session is disabled', function (): void {
    $locationData = LocationData::fromArray(['ipAddress' => '2.2.2.2']);

    $configMockForNoSessionTest = Mockery::mock(ConfigRepository::class);
    $configMockForNoSessionTest->shouldReceive('get')->with('meridian.geolocation.driver')->andReturn('maxmind_database');
    $configMockForNoSessionTest->shouldReceive('get')->with('meridian.geolocation.session.key', 'meridian_location')->andReturn('test_session_key_disabled_store');
    $configMockForNoSessionTest->shouldReceive('get')->with('meridian.geolocation.session.store', false)->andReturn(false); // Session disabled

    $sessionMockForDisabledTest = Mockery::mock(SessionStore::class);
    $sessionMockForDisabledTest->shouldNotReceive('put');

    $serviceWithSessionDisabled = new GeoLocationService($this->mockApp, $configMockForNoSessionTest, $sessionMockForDisabledTest);
    $serviceWithSessionDisabled->storeLocationInSession($locationData); // Should do nothing
});

test('clearLocationFromSession does nothing when session is disabled', function (): void {
    $configMockForNoSessionTest = Mockery::mock(ConfigRepository::class);
    $configMockForNoSessionTest->shouldReceive('get')->with('meridian.geolocation.driver')->andReturn('maxmind_database');
    $configMockForNoSessionTest->shouldReceive('get')->with('meridian.geolocation.session.key', 'meridian_location')->andReturn('test_session_key_disabled_clear');
    $configMockForNoSessionTest->shouldReceive('get')->with('meridian.geolocation.session.store', false)->andReturn(false); // Session disabled

    $sessionMockForDisabledTest = Mockery::mock(SessionStore::class);
    $sessionMockForDisabledTest->shouldNotReceive('forget');

    $serviceWithSessionDisabled = new GeoLocationService($this->mockApp, $configMockForNoSessionTest, $sessionMockForDisabledTest);
    $serviceWithSessionDisabled->clearLocationFromSession(); // Should do nothing
});
